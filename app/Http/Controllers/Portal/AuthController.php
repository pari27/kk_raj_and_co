<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
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
        $signedIn = $customer && Auth::guard('customer')->attempt([
            'id' => $customer->id,
            'email' => $customer->email,
            'password' => $request->input('password'),
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $signedIn) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'These details do not match an active client account.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $customer->forceFill([
            'last_login_at' => now(),
            'last_login_device' => Customer::deviceFrom($request->userAgent()),
            'portal_status' => 'activated',
        ])->save();

        return redirect()->intended(route('customer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
