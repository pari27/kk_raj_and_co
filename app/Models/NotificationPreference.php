<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'documents', 'tickets', 'payments', 'enquiries', 'clients', 'security', 'email'])]
class NotificationPreference extends Model
{
    public const LABELS = [
        'documents' => 'New documents to check',
        'tickets' => 'Services assigned to me',
        'payments' => 'Payments received',
        'enquiries' => 'New enquiries',
        'clients' => 'New clients added by staff',
        'security' => 'Security alerts',
        'email' => 'Also send to my email',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'boolean',
            'tickets' => 'boolean',
            'payments' => 'boolean',
            'enquiries' => 'boolean',
            'clients' => 'boolean',
            'security' => 'boolean',
            'email' => 'boolean',
        ];
    }

    public static function forUser(User $user): self
    {
        return static::query()->firstOrCreate(['user_id' => $user->id], [
            'documents' => true, 'tickets' => true, 'payments' => true,
            'enquiries' => true, 'clients' => true, 'security' => true, 'email' => false,
        ]);
    }
}
