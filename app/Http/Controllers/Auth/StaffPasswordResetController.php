<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StaffResetPasswordRequest;
use App\Http\Requests\Portal\SendCodeRequest;
use App\Http\Requests\Portal\VerifyCodeRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClientOtpService;
use App\Services\StaffOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Forgot password for Admin and Employee: email, then a 6-digit code, then a new password.
 */
class StaffPasswordResetController extends Controller
{
    /** How long a verified staff member has to choose a new password. */
    private const NEW_PASSWORD_WINDOW_MINUTES = 15;

    public function __construct(private readonly StaffOtpService $otps) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a code. The reply is the same whether or not the email belongs to an
     * account, so this can't be used to find out who works at the firm.
     */
    public function send(SendCodeRequest $request): RedirectResponse
    {
        if (! Setting::notificationsEnabled()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Email is switched off right now, so a code cannot be sent. Ask an administrator.',
            ]);
        }

        $user = $this->otps->accountFor($request->input('email'));

        if ($user) {
            $this->otps->send($user);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an active account exists for that email, we have emailed a 6-digit code. It expires in '.ClientOtpService::EXPIRES_IN_MINUTES.' minutes.')
            ->with('otp_expires_at', now()->addMinutes(ClientOtpService::EXPIRES_IN_MINUTES)->timestamp);
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $key = 'staff-otp|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withInput($request->only('email'))->withErrors([
                'code' => 'Too many attempts. Please wait '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $user = $this->otps->accountFor($email);

        if (! $user || ! $this->otps->verify($user, $request->input('code'))) {
            RateLimiter::hit($key, 60);

            // Keep the countdown running after a wrong code.
            $request->session()->keep('otp_expires_at');

            return back()->withInput($request->only('email'))->withErrors([
                'code' => 'That code is not right or has expired. Request a new one and try again.',
            ]);
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();
        $request->session()->put('staff_reset_verified', [
            'user_id' => $user->id,
            'until' => now()->addMinutes(self::NEW_PASSWORD_WINDOW_MINUTES)->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedUser($request)) {
            return redirect()->route('password.request')->withErrors([
                'code' => 'Please verify your email with a code first.',
            ]);
        }

        return view('auth.reset-password');
    }

    public function update(StaffResetPasswordRequest $request): RedirectResponse
    {
        $user = $this->verifiedUser($request);

        if (! $user) {
            return redirect()->route('password.request')->withErrors([
                'code' => 'That step has expired. Please verify your email with a new code.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('staff_reset_verified');

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in.');
    }

    /**
     * The staff member who just proved their email with a code, if that is still fresh.
     */
    private function verifiedUser(Request $request): ?User
    {
        $verified = $request->session()->get('staff_reset_verified');

        if (! is_array($verified) || ($verified['until'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::query()->whereKey($verified['user_id'])->first();

        return $user && $this->otps->accountFor($user->email)?->is($user) ? $user : null;
    }
}
