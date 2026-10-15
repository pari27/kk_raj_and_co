<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ClientPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function show(): View
    {
        $customer = Auth::guard('customer')->user();
        $contact = $this->portal->mainContact($this->portal->services($customer));

        return view('customer.profile', [
            'customer' => $customer,
            'contact' => $contact ? $this->portal->contactCard($contact) : null,
        ]);
    }

    public function emailUpdates(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $customer->update(['email_notifications_enabled' => $request->boolean('email_updates')]);

        return back();
    }

    public function editPassword(): View
    {
        return view('customer.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'Your new password must be different from the current one.',
        ]);

        if (! Hash::check($validated['current_password'], $customer->password)) {
            return back()->withErrors(['current_password' => 'That is not your current password.']);
        }

        if (in_array(mb_strtolower($validated['password']), array_filter([mb_strtolower((string) $customer->email), (string) $customer->phone]), true)) {
            return back()->withErrors(['password' => "Your password can't be your email or mobile number."]);
        }

        $customer->forceFill(['password' => $validated['password'], 'password_changed_at' => now()])->save();

        AuditLogger::log(
            action: 'Updated',
            module: 'Client Portal',
            recordLabel: $customer->name,
            details: 'Client changed their portal password',
            subject: $customer,
        );

        return redirect()->route('customer.profile')->with('status', 'Your password was changed.');
    }
}
