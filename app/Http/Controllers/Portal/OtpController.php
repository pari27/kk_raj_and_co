<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SendCodeRequest;
use App\Http\Requests\Portal\VerifyCodeRequest;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\ClientOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class OtpController extends Controller
{
    /** How long a verified client has to choose a password. */
    private const SET_PASSWORD_WINDOW_MINUTES = 15;

    public function __construct(private readonly ClientOtpService $otps) {}

    public function create(): View
    {
        return view('customer.auth.verify-otp');
    }

    /**
     * Email a code. The reply is the same whether or not the email belongs to a
     * client, so this can't be used to find out who the firm's clients are.
     */
    public function send(SendCodeRequest $request): RedirectResponse
    {
        $email = $request->input('email');

        if (! Setting::notificationsEnabled()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Email is switched off for this firm right now, so a code cannot be sent. Please contact the firm.',
            ]);
        }

        $customer = Customer::portalAccountFor($email);

        if ($customer && $customer->email) {
            $this->otps->send($customer);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an active client account exists for that email, we have emailed a 6-digit code. It expires in '.ClientOtpService::EXPIRES_IN_MINUTES.' minutes.');
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $key = 'client-otp|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withInput($request->only('email'))->withErrors([
                'code' => 'Too many attempts. Please wait '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $customer = Customer::portalAccountFor($email);

        if (! $customer || ! $this->otps->verify($customer, $request->input('code'))) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('email'))->withErrors([
                'code' => 'That code is not right or has expired. Request a new one and try again.',
            ]);
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();
        $request->session()->put('portal_otp_verified', [
            'customer_id' => $customer->id,
            'until' => now()->addMinutes(self::SET_PASSWORD_WINDOW_MINUTES)->timestamp,
        ]);

        return redirect()->route('customer.set-password');
    }
}
