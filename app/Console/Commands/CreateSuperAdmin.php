<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Super Admin accounts are for the software team only, so they can only be created here, on the server.
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'superadmin:create
        {email : Sign-in email for the Super Admin}
        {name=Super Admin : Display name}
        {--password= : Password (if left out, you are asked for it)}
        {--reset-password : Change the password of an existing Super Admin}';

    protected $description = 'Create a Super Admin (software team) account, or reset its password';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! $existing->isSuperAdmin()) {
            $this->error("{$email} already belongs to a different kind of account. Use another email.");

            return self::FAILURE;
        }

        if ($existing && ! $this->option('reset-password')) {
            $this->error("{$email} is already a Super Admin. Add --reset-password to change the password.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password (at least 8 characters)'));

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(8)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = $existing ?? new User(['email' => $email, 'name' => $this->argument('name'), 'role' => UserRole::SuperAdmin]);
        $user->password = $password;
        $user->is_active = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info($existing ? "Password changed for {$email}." : "Super Admin created: {$email}. Sign in at the normal staff login page.");

        return self::SUCCESS;
    }
}
