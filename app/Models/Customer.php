<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A client of the firm. Also the account clients use to sign in to the client
 * portal, through its own `customer` guard (never the staff `web` guard).
 * The password is set by the client after verifying a one-time code, so it is
 * not mass assignable.
 */
#[Fillable(['name', 'phone', 'email', 'is_active', 'portal_status', 'email_notifications_enabled', 'created_by'])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * "Firefox on Windows" from a browser's user-agent string, for the Profile page.
     */
    public static function deviceFrom(?string $userAgent): ?string
    {
        $agent = (string) $userAgent;

        if ($agent === '') {
            return null;
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} on {$system}" : $browser;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function otps(): MorphMany
    {
        return $this->morphMany(Otp::class, 'owner');
    }

    /**
     * The one active client who owns this email, or null. When the email is
     * shared by several clients (or none) nobody is returned, so a code or a
     * login can never reach the wrong person.
     */
    public static function portalAccountFor(string $email): ?self
    {
        $matches = static::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function hasPortalPassword(): bool
    {
        return $this->password !== null;
    }
}
