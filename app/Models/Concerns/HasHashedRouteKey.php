<?php

namespace App\Models\Concerns;

use Vinkla\Hashids\Facades\Hashids;

/**
 * Uses an obfuscated Hashids string (instead of the raw numeric id) as this
 * model's route-binding key, so URLs don't expose sequential/guessable ids.
 * This is obscurity, not access control — every route must still authorize
 * the current user against the resolved record.
 */
trait HasHashedRouteKey
{
    public function getRouteKey(): string
    {
        return Hashids::encode($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);

        if (empty($decoded)) {
            return null;
        }

        return $this->where($this->getKeyName(), $decoded[0])->first();
    }
}
