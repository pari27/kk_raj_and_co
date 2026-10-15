<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single-use, short-lived code. Only its hash is stored.
 */
#[Fillable(['owner_type', 'owner_id', 'purpose', 'code_hash', 'expires_at', 'consumed_at', 'attempts'])]
class Otp extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
