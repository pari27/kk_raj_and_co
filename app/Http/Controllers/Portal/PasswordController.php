<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SetPasswordRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedCustomer($request)) {
            return redirect()->route('customer.verify-otp')->withErrors([
                'code' => 'Please verify your email with a code first.',
            ]);
        }

        return view('customer.auth.set-password');
    }

    public function store(SetPasswordRequest $request): RedirectResponse
    {
        $customer = $this->verifiedCustomer($request);

        if (! $customer) {
            return redirect()->route('customer.verify-otp')->withErrors([
                'code' => 'That step has expired. Please verify your email with a new code.',
            ]);
        }

        $firstTime = ! $customer->hasPortalPassword();

        $customer->forceFill([
            'password' => $request->input('password'),
            'portal_status' => 'activated',
            'last_login_at' => now(),
            'last_login_device' => Customer::deviceFrom($request->userAgent()),
            'password_changed_at' => now(),
        ])->save();

        AuditLogger::log(
            action: 'Updated',
            module: 'Client Portal',
            recordLabel: $customer->name,
            details: $firstTime ? 'Client set a portal password (first login)' : 'Client reset their portal password',
            subject: $customer,
        );

        $request->session()->forget('portal_otp_verified');

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    /**
     * The client who just proved their email with a code, if that is still fresh.
     */
    private function verifiedCustomer(Request $request): ?Customer
    {
        $verified = $request->session()->get('portal_otp_verified');

        if (! is_array($verified) || ($verified['until'] ?? 0) < now()->timestamp) {
            return null;
        }

        return Customer::query()->whereKey($verified['customer_id'])->where('is_active', true)->first();
    }
}
