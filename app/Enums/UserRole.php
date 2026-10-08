<?php

namespace App\Enums;

use App\Models\User;

/**
 * Canonical role codes used app-wide, including for "who did this" tagging in
 * history/audit tables. Customer accounts live in the separate `customers`
 * table (own auth guard), so `Customer` is never stored in `users.role` —
 * see `internalRoles()`.
 *
 * @see User::$fillable
 */
enum UserRole: int
{
    case SuperAdmin = 0;
    case Admin = 1;
    case Employee = 2;
    case Customer = 3;

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Employee => 'Employee',
            self::Customer => 'Customer',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function internalRoles(): array
    {
        return [self::SuperAdmin, self::Admin, self::Employee];
    }
}
