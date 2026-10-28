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

    /**
     * The colour and icon this update is shown with, from what it is about.
     *
     * @return array{0: string, 1: string}
     */
    public function look(): array
    {
        $title = strtolower($this->title);

        return match (true) {
            str_contains($title, 'verified') => ['green', 'check'],
            str_contains($title, 're-upload') => ['red', 'upload'],
            str_contains($title, 'completed') => ['green', 'download'],
            $this->type === 'payment' => ['red', 'rupee'],
            default => ['gray', 'list'],
        };
    }}
