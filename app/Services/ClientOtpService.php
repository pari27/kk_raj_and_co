<?php

namespace App\Services;

use App\Mail\ClientOtpMail;
use App\Models\Customer;
use App\Models\Otp;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * One-time codes for the client portal: first login and forgot password.
 *
 * The code is six digits from a secure random source, valid for 10 minutes, used
 * once, and only its hash is stored. Five wrong tries kill it, and a new one can
 * be requested at most once a minute.
 */
class ClientOtpService
{
    public const EXPIRES_IN_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_AFTER_SECONDS = 60;

    public const SENT = 'sent';

    public const TOO_SOON = 'too_soon';

    public const EMAIL_OFF = 'email_off';

    /**
     * Email a fresh code to the client.
     */
    public function send(Customer $customer): string
    {
        if (! Setting::notificationsEnabled()) {
            return self::EMAIL_OFF;
        }

        $recentlySent = $customer->otps()
            ->where('created_at', '>', now()->subSeconds(self::RESEND_AFTER_SECONDS))
            ->exists();

        if ($recentlySent) {
            return self::TOO_SOON;
        }

        // Only the newest code works.
        $customer->otps()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $customer->otps()->create([
            'purpose' => $this->purposeFor($customer),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        $recipient = Setting::testModeEnabled() ? config('mail.test_recipient') : $customer->email;

        Mail::to($recipient)->send(new ClientOtpMail($customer, $code));

        return self::SENT;
    }

    /**
     * Check a code the client typed. A correct code is used up; a wrong one counts as a try.
     */
    public function verify(Customer $customer, string $code): bool
    {
        /** @var Otp|null $otp */
        $otp = $customer->otps()
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function purposeFor(Customer $customer): string
    {
        return $customer->hasPortalPassword() ? 'forgot_password' : 'login';
    }
}
