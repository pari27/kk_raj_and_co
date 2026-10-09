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
        $testMode = $request->boolean('test_mode');

        $setting->update(['test_mode' => $testMode]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Settings',
            recordLabel: 'Test mode',
            details: $testMode
                ? "Test mode turned on — notifications now go to {$this->testRecipientLabel()}"
                : 'Test mode turned off — notifications now go to real recipients',
            subject: $setting,
        );

        return redirect()
            ->route('admin.settings.firm-profile')
            ->with('status', $testMode ? 'Test mode is now on.' : 'Test mode is now off.');
    }

    private function testRecipientLabel(): string
    {
        return (string) (config('mail.test_recipient') ?: '(no MAIL_TEST_RECIPIENT configured)');
    }
}
