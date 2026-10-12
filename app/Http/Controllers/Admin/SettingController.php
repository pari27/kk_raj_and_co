<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $setting = Setting::current();

        return view('admin.settings.firm-profile', compact('setting'));
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

    private function testRecipientLabel(): string
    {
        return (string) (config('mail.test_recipient') ?: '(no MAIL_TEST_RECIPIENT configured)');
    }
}
