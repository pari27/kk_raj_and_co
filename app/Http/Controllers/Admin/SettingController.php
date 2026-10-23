<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $setting = Setting::current();

        return view('admin.settings.firm-profile', compact('setting'));
    }

    public function editPaymentDetails(): View
    {
        return view('admin.settings.payment-details', ['setting' => Setting::current()]);
    }

    public function updatePaymentDetails(Request $request): RedirectResponse
    {
        $request->merge(['payment_bank_ifsc' => strtoupper(trim((string) $request->input('payment_bank_ifsc')))]);

        $validated = $request->validate([
            'payment_upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]{2,}@[A-Za-z0-9]{2,}$/'],
            'payment_bank_account_name' => ['nullable', 'string', 'max:120'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 ]{6,40}$/'],
            'payment_bank_ifsc' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'payment_bank_branch' => ['nullable', 'string', 'max:160'],
            'payment_instructions' => ['nullable', 'string', 'max:1000'],
        ], [
            'payment_upi_id.regex' => 'Enter a UPI ID like name@bank.',
            'payment_bank_account_number.regex' => 'The account number can only have digits (and spaces), 6 or more.',
            'payment_bank_ifsc.regex' => 'An IFSC code has 4 letters, a 0, then 6 letters or digits (for example HDFC0001234).',
        ]);

        $setting = Setting::current();
        $fields = array_keys($validated);
        $before = $setting->only($fields);

        $setting->update(array_map(fn ($value) => $value === null || trim($value) === '' ? null : trim($value), $validated));

        if ($before !== $setting->fresh()->only($fields)) {
            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: 'Payment details',
                details: 'Payment details shown to clients were changed',
                subject: $setting,
            );
        }

        return redirect()->route('admin.settings.payment-details');
    }

    public function updateFirmProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'firm_name' => ['required', 'string', 'max:120'],
            'firm_address' => ['required', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $setting = Setting::current();
        $changes = [];

        if ($setting->firm_name !== $validated['firm_name']) {
            $changes[] = 'name';
        }
        if ($setting->firm_address !== $validated['firm_address']) {
            $changes[] = 'address';
        }

        $data = ['firm_name' => $validated['firm_name'], 'firm_address' => $validated['firm_address']];

        if ($request->hasFile('logo')) {
            if ($setting->firm_logo_path) {
                Storage::disk('public')->delete($setting->firm_logo_path);
            }
            $data['firm_logo_path'] = $request->file('logo')->store('branding', 'public');
            $changes[] = 'logo';
        } elseif ($request->boolean('remove_logo') && $setting->firm_logo_path) {
            Storage::disk('public')->delete($setting->firm_logo_path);
            $data['firm_logo_path'] = null;
            $changes[] = 'logo removed';
        }

        $setting->update($data);

        if ($changes !== []) {
            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: 'Firm profile',
                details: 'Firm profile changed: '.implode(', ', $changes),
                subject: $setting,
            );
        }

        return redirect()->route('admin.settings.firm-profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = Setting::current();
        $wasNotificationsEnabled = $setting->notifications_enabled;
        $wasTestMode = $setting->test_mode;

        $notificationsEnabled = $request->boolean('notifications_enabled');
        $testMode = $request->boolean('test_mode');

        $setting->update([
            'notifications_enabled' => $notificationsEnabled,
            'test_mode' => $testMode,
        ]);

        if ($notificationsEnabled !== $wasNotificationsEnabled) {
            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: 'Email notifications',
                details: $notificationsEnabled
                    ? 'Email notifications turned on'
                    : 'Email notifications turned off — no outbound email will be sent',
                subject: $setting,
            );
        }

        if ($testMode !== $wasTestMode) {
            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: 'Test mode',
                details: $testMode
                    ? "Test mode turned on — notifications now go to {$this->testRecipientLabel()}"
                    : 'Test mode turned off — notifications now go to real recipients',
                subject: $setting,
            );
        }

        return redirect()
            ->route('admin.settings.firm-profile')
            ->with('status', 'Settings updated.');
    }

    public function updateNumbering(Request $request): RedirectResponse
    {
        // Compare prefixes case-insensitively: they are always stored in capitals.
        $request->merge([
            'service_prefix' => strtoupper((string) $request->input('service_prefix')),
            'enquiry_prefix' => strtoupper((string) $request->input('enquiry_prefix')),
        ]);

        $validated = $request->validate([
            'service_prefix' => ['required', 'string', 'regex:/^[A-Za-z0-9]{2,6}$/'],
            'enquiry_prefix' => ['required', 'string', 'regex:/^[A-Za-z0-9]{2,6}$/', 'different:service_prefix'],
        ], [
            'service_prefix.regex' => 'Use 2 to 6 letters or numbers, with no spaces or symbols.',
            'enquiry_prefix.regex' => 'Use 2 to 6 letters or numbers, with no spaces or symbols.',
            'enquiry_prefix.different' => 'The enquiry prefix must be different from the service prefix.',
        ]);

        $setting = Setting::current();

        foreach (['service_prefix' => 'Service', 'enquiry_prefix' => 'Enquiry'] as $field => $label) {
            $before = $setting->{$field};
            $prefix = strtoupper($validated[$field]);

            if ($prefix === $before) {
                continue;
            }

            $setting->{$field} = $prefix;

            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: "{$label} number prefix",
                details: "{$label} number prefix changed from {$before} to {$prefix}",
                subject: $setting,
            );
        }

        $setting->save();

        return redirect()->route('admin.settings.firm-profile');
    }

    private function testRecipientLabel(): string
    {
        return (string) (config('mail.test_recipient') ?: '(no MAIL_TEST_RECIPIENT configured)');
    }
}
