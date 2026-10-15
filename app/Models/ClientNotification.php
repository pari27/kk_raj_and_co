<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in a client's "Updates" feed in the portal.
 */
#[Fillable(['customer_id', 'type', 'title', 'body', 'url', 'read_at'])]
class ClientNotification extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
