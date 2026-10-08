<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only, system-wide record of real actions taken in the app.
 * Never updated or deleted once written.
 */
#[Fillable(['user_id', 'action', 'module', 'subject_type', 'subject_id', 'record_label', 'record_url', 'details', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A short "Browser · OS" label parsed from the stored user agent.
     */
    public function device(): string
    {
        $userAgent = $this->user_agent ?? '';

        if ($userAgent === '') {
            return '—';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };

        return "{$browser} · {$platform}";
    }
}
