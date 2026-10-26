<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(
        string $action,
        string $module,
        ?string $recordLabel = null,
        ?string $recordUrl = null,
        ?string $details = null,
        ?Model $subject = null,
        ?int $userId = null,
    ): AuditLog {
        // The software team's own activity is not recorded: it must not show up in the firm's Audit Log.
        $actor = $userId !== null ? User::query()->find($userId) : Auth::guard('web')->user();

        if ($actor?->isSuperAdmin()) {
            return new AuditLog;
        }

        return AuditLog::create([
            // The staff login only: a client's id must never land in a column that points at users.
            'user_id' => $userId ?? Auth::guard('web')->id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'record_label' => $recordLabel,
            'record_url' => $recordUrl,
            'details' => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
