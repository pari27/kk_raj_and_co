<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the fixed Super Admin account.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            if (app()->environment('local', 'testing')) {
                $email ??= 'superadmin@example.com';
                $password ??= 'password';
            } else {
                throw new RuntimeException(
                    'SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD must be set in .env before seeding outside local/testing.'
                );
            }
        }

        User::query()->updateOrCreate(
            ['role' => UserRole::SuperAdmin],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => $email,
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
