<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\DismissedError;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Where the software team checks on the system: its health, and what has gone wrong.
 * Super Admin only. Nothing here changes any data.
 */
class SystemController extends Controller
{
    /** How much of the end of the log file is read (the file itself can be very large). */
    private const LOG_TAIL_BYTES = 600_000;

    private const MAX_ERRORS = 60;

    public function index(Request $request): View
    {
        $all = $this->recentErrors();
        $dismissed = DismissedError::query()->pluck('fingerprint')->flip();
        $resolved = array_values(array_filter($all, fn (array $e) => isset($dismissed[$e['fingerprint']])));
        $open = array_values(array_filter($all, fn (array $e) => ! isset($dismissed[$e['fingerprint']])));
        $showResolved = $request->query('show') === 'resolved';
        $errors = $showResolved ? $resolved : $open;

        return view('admin.system.index', [
            'health' => $this->health(),
            'counts' => $this->counts(),
            'errors' => $errors,
            'resolvedCount' => count($resolved),
            'showResolved' => $showResolved,
            'logSize' => is_file(storage_path('logs/laravel.log')) ? filesize(storage_path('logs/laravel.log')) : 0,
        ]);
    }

    /**
     * Mark one error as resolved. It stops showing here; the log file is not changed.
     */
    public function resolve(Request $request): RedirectResponse
    {
        $validated = $request->validate(['fingerprint' => ['required', 'string', 'size:40']]);

        $match = collect($this->recentErrors())->firstWhere('fingerprint', $validated['fingerprint']);

        DismissedError::query()->firstOrCreate(
            ['fingerprint' => $validated['fingerprint']],
            ['message' => $match['message'] ?? null],
        );

        return redirect()->route('super.system');
    }

    /**
     * Mark every error currently shown as resolved.
     */
    public function resolveAll(): RedirectResponse
    {
        $dismissed = DismissedError::query()->pluck('fingerprint')->flip();

        foreach ($this->recentErrors() as $error) {
            if (! isset($dismissed[$error['fingerprint']])) {
                DismissedError::query()->create(['fingerprint' => $error['fingerprint'], 'message' => $error['message']]);
            }
        }

        return redirect()->route('super.system');
    }

    /**
     * Show a resolved error in the list again.
     */
    public function reopen(Request $request): RedirectResponse
    {
        $validated = $request->validate(['fingerprint' => ['required', 'string', 'size:40']]);

        DismissedError::query()->where('fingerprint', $validated['fingerprint'])->delete();

        return redirect()->route('super.system', ['show' => 'resolved']);
    }

    public function downloadLog(): BinaryFileResponse
    {
        $path = storage_path('logs/laravel.log');

        abort_unless(is_file($path), 404);

        return response()->download($path, 'laravel-'.now()->format('Y-m-d-His').'.log');
    }

    /**
     * @return array<string, array{value: string, ok: bool|null}>
     */
    private function health(): array
    {
        try {
            DB::select('select 1');
            $database = ['value' => 'Connected ('.config('database.default').')', 'ok' => true];
        } catch (Throwable $e) {
            $database = ['value' => 'Not reachable', 'ok' => false];
        }

        $setting = Setting::query()->first();

        return [
            'Application' => ['value' => config('app.firm_name').' · '.config('app.env'), 'ok' => null],
            'Debug mode' => ['value' => config('app.debug') ? 'ON (should be off on a live system)' : 'Off', 'ok' => ! config('app.debug')],
            'Laravel / PHP' => ['value' => app()->version().' / '.PHP_VERSION, 'ok' => null],
            'Database' => $database,
            'Email delivery' => ['value' => 'Mailer: '.config('mail.default'), 'ok' => config('mail.default') !== 'log'],
            'Email notifications' => ['value' => ($setting?->notifications_enabled ?? true) ? 'On' : 'Off', 'ok' => null],
            'Test mode' => ['value' => ($setting?->test_mode ?? false) ? 'ON: all email goes to the test address' : 'Off (real recipients)', 'ok' => null],
            'File storage' => ['value' => is_writable(storage_path('logs')) ? 'Writable' : 'Not writable', 'ok' => is_writable(storage_path('logs'))],
            'Public uploads' => ['value' => Storage::disk('public')->exists('.') ? 'Available' : 'Missing', 'ok' => Storage::disk('public')->exists('.')],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'Admins' => User::query()->where('role', \App\Enums\UserRole::Admin)->count(),
            'Employees' => User::query()->where('role', \App\Enums\UserRole::Employee)->count(),
            'Clients' => Customer::query()->count(),
            'Enquiries' => Enquiry::query()->count(),
            'Services' => Ticket::query()->count(),
            'Payments' => Payment::query()->count(),
            'Audit log entries' => AuditLog::query()->count(),
        ];
    }

    /**
     * The newest error entries in the application log, newest first.
     *
     * @return array<int, array{fingerprint: string, time: string, level: string, message: string, detail: string}>
     */
    private function recentErrors(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path) || ($size = filesize($path)) === 0) {
            return [];
        }

        $handle = fopen($path, 'rb');
        $start = max(0, $size - self::LOG_TAIL_BYTES);
        fseek($handle, $start);
        $text = (string) stream_get_contents($handle);
        fclose($handle);

        if ($start > 0) {
            $text = substr($text, (int) strpos($text, "\n") + 1); // drop the cut-off first line
        }

        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] )/m', $text, -1, PREG_SPLIT_NO_EMPTY);
        $entries = [];

        foreach (array_reverse($parts) as $part) {
            if (! preg_match('/^\[(?<time>[\d\- :]+)\] (?<env>[\w\-]+)\.(?<level>[A-Z]+): (?<rest>.*)$/s', $part, $m)) {
                continue;
            }

            // Entries written while the automated tests run are not real problems.
            if ($m['env'] === 'testing') {
                continue;
            }

            if (! in_array($m['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
                continue;
            }

            $lines = preg_split('/\R/', trim($m['rest']));
            $entries[] = [
                'fingerprint' => sha1($m['time'].'|'.$m['level'].'|'.$lines[0]),
                'time' => $m['time'],
                'level' => $m['level'],
                'message' => mb_strimwidth($lines[0], 0, 300, '…'),
                'detail' => implode("\n", array_slice($lines, 0, 25)),
            ];

            if (count($entries) >= self::MAX_ERRORS) {
                break;
            }
        }

        return $entries;
    }
}
