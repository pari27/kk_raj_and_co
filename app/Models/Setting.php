<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_mode'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'test_mode' => 'boolean',
        ];
    }

    /**
     * This app has a single settings row; fetch it, creating it with
     * defaults the first time it's needed.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['test_mode' => false]);
    }

    public static function testModeEnabled(): bool
    {
        return static::current()->test_mode;
    }
}
