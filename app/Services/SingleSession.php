<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One signed-in device per account (Super Admin, Admin, Employee and client alike).
 *
 * Signing in claims the account with a fresh token, kept in the session and on the
 * account's row. A session whose token no longer matches has been replaced by a
 * newer sign-in and is signed out. Open pages ping the server every few seconds,
 * which also tells us the device is still there.
 */
class SingleSession
{
    /** A device that has not been heard from for this long no longer counts as signed in. */
    public const STALE_AFTER_MINUTES = 5;

    /** Write "last seen" to the database at most this often. */
    public const TOUCH_EVERY_SECONDS = 30;

    public const REPLACED_MESSAGE = 'You have been logged out because someone logged in to this account from another device.';

    /**
     * Is the account already signed in on a device that is still around?
     */
    public function hasActiveSession(Authenticatable $user): bool
    {
        return $user->login_token !== null
            && $user->session_seen_at !== null
            && $user->session_seen_at->gt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * Make this session the account's one session. Any other one is replaced.
     */
    public function claim(Authenticatable $user, string $guard, Request $request): void
    {
        $token = Str::random(40);

        $this->store($user, ['login_token' => $token, 'session_seen_at' => now()]);

        $request->session()->put('login_token_'.$guard, $token);
        $request->session()->put('login_touched_'.$guard, now()->timestamp);
    }

    /**
     * Does this session still own the account?
     */
    public function owns(Authenticatable $user, string $guard, Request $request): bool
    {
        return $user->login_token !== null
            && hash_equals($user->login_token, (string) $request->session()->get('login_token_'.$guard));
    }

    /**
     * Note that the device is still here, without writing on every request.
     */
    public function touch(Authenticatable $user, string $guard, Request $request): void
    {
        $key = 'login_touched_'.$guard;

        if (now()->timestamp - (int) $request->session()->get($key, 0) < self::TOUCH_EVERY_SECONDS) {
            return;
        }

        $this->store($user, ['session_seen_at' => now()]);
        $request->session()->put($key, now()->timestamp);
    }

    /**
     * The person signed out: free the account, but only if this session still owned it.
     */
    public function release(Authenticatable $user, string $guard, Request $request): void
    {
        if ($this->owns($user, $guard, $request)) {
            $this->store($user, ['login_token' => null, 'session_seen_at' => null]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function store(Authenticatable $user, array $values): void
    {
        // A plain query so no model events run and "updated at" is left alone.
        $user->newQuery()->whereKey($user->getKey())->update($values);

        foreach ($values as $column => $value) {
            $user->setAttribute($column, $value);
        }

        $user->syncOriginalAttributes(array_keys($values));
    }
}
