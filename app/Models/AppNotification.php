<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

#[Fillable(['user_id', 'type', 'title', 'body', 'link_text', 'link_label', 'url', 'read_at'])]
class AppNotification extends Model
{
    public const TYPES = [
        'documents' => 'Documents',
        'tickets' => 'Tickets',
        'payments' => 'Payments',
        'enquiries' => 'Enquiries',
        'security' => 'Security',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Body is stored as plain text; **text** marks the bold parts.
     */
    public function renderBody(): HtmlString
    {
        $escaped = e($this->body);

        return new HtmlString(preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped));
    }
}
