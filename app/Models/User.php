<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'employee_id', 'created_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasHashedRouteKey;

    /**
     * Keep the employees table's name copy in sync whenever an employee's
     * account name is set or changed.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->role === UserRole::Employee && $user->employee_id) {
                $user->profile?->update(['name' => $user->name]);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'session_seen_at' => 'datetime',
        ];
    }

    /**
     * Admin access. A Super Admin has all of it too, so every Admin check also lets a Super Admin through.
     */
    /**
     * A Super Admin (the software team) appears as plain "Admin" to everyone else, wherever a
     * name is shown: created by, received by, uploaded by, notifications. They see their own name.
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): mixed {
            $isSuperAdmin = (int) ($attributes['role'] ?? -1) === UserRole::SuperAdmin->value;

            if (! $isSuperAdmin) {
                return $value;
            }

            $viewer = \Illuminate\Support\Facades\Auth::guard('web')->user();

            return $viewer && $viewer->getKey() === ($attributes['id'] ?? null) ? $value : 'Admin';
        });
    }

    public function isAdmin(): bool    {
        return $this->role === UserRole::Admin || $this->role === UserRole::SuperAdmin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    /**
     * The person who added this account.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function otps(): MorphMany
    {
        return $this->morphMany(Otp::class, 'owner');
    }

    /**
     * Whether this employee has completed their first-login password setup.
     */
    public function hasSetPassword(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Where mail-channel notifications (e.g. password reset) are delivered.
     * Redirected to the test recipient while Test mode is on.
     */
    public function routeNotificationForMail(): string
    {
        return Setting::testModeEnabled()
            ? (string) config('mail.test_recipient')
            : $this->email;
    }
}
