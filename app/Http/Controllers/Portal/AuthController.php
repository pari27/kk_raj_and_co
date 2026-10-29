<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use App\Models\Customer;
use App\Services\SingleSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** How long the same person has to sign in again, knowing the other device will be signed out. */
    private const CONFLICT_WINDOW_MINUTES = 5;

    public function __construct(private readonly SingleSession $single) {}

    public function create(): View
    {
        return view('customer.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $key = 'client-login|'.$request->input('email').'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Too many attempts. Please wait '.RateLimiter::availableIn($key).' seconds and try again.',
            ]);
        }

        $customer = Customer::portalAccountFor($request->input('email'));

        if ($customer && ! $customer->hasPortalPassword()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'You have not set a password yet. Use "First login or forgot password?" to get a code by email.',
            ]);
        }

        // Matching on the id as well keeps the check to exactly this one client.
        $credentials = $customer ? [
            'id' => $customer->id,
            'email' => $customer->email,
            'password' => $request->input('password'),
            'is_active' => true,
        ] : null;

        // Already signed in on another device: say so first. Signing in again takes over.
        $email = mb_strtolower(trim((string) $request->input('email')));
        $warned = $request->session()->get('login_conflict_customer');
        $alreadyWarned = is_array($warned) && ($warned['email'] ?? null) === $email && ($warned['until'] ?? 0) >= now()->timestamp;

        if (! $alreadyWarned && $credentials && Auth::guard('customer')->validate($credentials) && $this->single->hasActiveSession($customer)) {
            RateLimiter::clear($key);

            $request->session()->put('login_conflict_customer', [
                'email' => $email,
                'until' => now()->addMinutes(self::CONFLICT_WINDOW_MINUTES)->timestamp,
            ]);

            return redirect()->route('customer.login')
                ->withInput($request->only('email', 'remember'))
                ->with('session_conflict', true);
        }

        $signedIn = $credentials && Auth::guard('customer')->attempt($credentials, $request->boolean('remember'));

        if (! $signedIn) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'These details do not match an active client account.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('login_conflict_customer');
        $request->session()->regenerate();
        $customer->forceFill([
            'last_login_at' => now(),
            'last_login_device' => Customer::deviceFrom($request->userAgent()),
            'portal_status' => 'activated',
        ])->save();

        return redirect()->intended(route('customer.dashboard'));
    }

    /**
     * Lets the login page warn, as soon as an email is typed, that the account is signed in elsewhere.
     */
    public function sessionCheck(Request $request): JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        $customer = Customer::portalAccountFor($email);
        $busy = $customer !== null && $this->single->hasActiveSession($customer);

        // The page is about to show the warning, so signing in next counts as having been warned.
        if ($busy) {
            $request->session()->put('login_conflict_customer', [
                'email' => $email,
                'until' => now()->addMinutes(self::CONFLICT_WINDOW_MINUTES)->timestamp,
            ]);
        }

        return response()->json(['busy' => $busy]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
