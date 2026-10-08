<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class NumberSequence
{
    /**
     * Generate the next "{prefix}-{year}-{0001}" style number for a table,
     * scoped to the current calendar year. Must be called inside the same
     * DB transaction as the row insert that uses the returned number.
     */
    public static function next(string $table, string $prefix): string
    {
        $year = now()->year;
        $likePrefix = "{$prefix}-{$year}-";

        $last = DB::table($table)
            ->where('number', 'like', $likePrefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('number');

        $nextSequence = 1;

        if ($last) {
            $nextSequence = ((int) substr($last, strlen($likePrefix))) + 1;
        }

        return $likePrefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
