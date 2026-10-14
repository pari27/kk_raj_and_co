<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Admins see the whole trail; an employee only sees entries for their own actions.
     */
    private function scope(): Builder
    {
        $user = auth()->user();

        return AuditLog::query()->when($user->isEmployee(), fn (Builder $query) => $query->where('user_id', $user->id));
    }

    public function index(): View
    {
        $logs = $this->scope()->with('user')->latest('created_at')->latest('id')->get();
        $ownOnly = auth()->user()->isEmployee();

        $today = Carbon::today();

        $stats = [
            'actions_today' => $this->scope()->whereDate('created_at', $today)->count(),
            'logins_today' => $this->scope()->whereDate('created_at', $today)->where('action', 'Logged in')->count(),
            'records_changed_today' => $this->scope()->whereDate('created_at', $today)->where('module', '!=', 'Login')->count(),
            'failed_logins_today' => $this->scope()->whereDate('created_at', $today)->where('action', 'Failed login')->count(),
        ];

        $users = User::query()->when($ownOnly, fn ($query) => $query->whereKey(auth()->id()))->orderBy('name')->get(['id', 'name']);
        $modules = $this->scope()->distinct()->orderBy('module')->pluck('module');
        $actions = $this->scope()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-log', compact('logs', 'stats', 'users', 'modules', 'actions', 'ownOnly'));
    }

    public function export(): StreamedResponse
    {
        $logs = $this->scope()->with('user')->latest('created_at')->latest('id')->get();

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'User', 'Action', 'Module', 'Record', 'Details', 'IP Address', 'Device']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name ?? 'System',
                    $log->action,
                    $log->module,
                    $log->record_label,
                    $log->details,
                    $log->ip_address,
                    $log->device(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
