<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_mode', 'notifications_enabled'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'test_mode' => 'boolean',
            'notifications_enabled' => 'boolean',
        ];
    }

    /**
     * This app has a single settings row; fetch it, creating it with
     * defaults the first time it's needed.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'test_mode' => false,
            'notifications_enabled' => true,
        ]);
    }

    public static function testModeEnabled(): bool
    {
        return static::current()->test_mode;
    }

    /**
     * Master switch: when off, no outbound email is sent at all,
     * regardless of Test mode.
     */
    public static function notificationsEnabled(): bool
    {
        return static::current()->notifications_enabled;
    }
}
