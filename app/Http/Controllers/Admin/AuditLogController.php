<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::query()->with('user')->latest('created_at')->latest('id')->get();

        $today = Carbon::today();

        $stats = [
            'actions_today' => AuditLog::query()->whereDate('created_at', $today)->count(),
            'logins_today' => AuditLog::query()->whereDate('created_at', $today)->where('action', 'Logged in')->count(),
            'records_changed_today' => AuditLog::query()->whereDate('created_at', $today)->where('module', '!=', 'Login')->count(),
            'failed_logins_today' => AuditLog::query()->whereDate('created_at', $today)->where('action', 'Failed login')->count(),
        ];

        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $modules = AuditLog::query()->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-log', compact('logs', 'stats', 'users', 'modules', 'actions'));
    }

    public function export(): StreamedResponse
    {
        $logs = AuditLog::query()->with('user')->latest('created_at')->latest('id')->get();

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
