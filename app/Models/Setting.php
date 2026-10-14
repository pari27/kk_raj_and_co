<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_mode', 'notifications_enabled', 'service_prefix', 'enquiry_prefix'])]
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
            'service_prefix' => 'SRV',
            'enquiry_prefix' => 'ENQ',
        ]);
    }

    /**
     * Prefix used when numbering new services, e.g. "SRV" in SRV-2026-0001.
     */
    public static function servicePrefix(): string
    {
        return static::current()->service_prefix ?: 'SRV';
    }

    /**
     * Prefix used when numbering new enquiries, e.g. "ENQ" in ENQ-2026-0001.
     */
    public static function enquiryPrefix(): string
    {
        return static::current()->enquiry_prefix ?: 'ENQ';
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
