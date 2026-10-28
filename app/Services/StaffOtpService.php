<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\StaffOtpMail;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * One-time codes for staff (Admin and Employee) who forgot their password.
 *
 * Same rules as the client portal's codes: six digits, valid for 10 minutes, used
 * once, only the hash is stored, five wrong tries kill it, one new code a minute.
 * The hidden Super Admin account never gets a code.
 */
class StaffOtpService
{
    public const PURPOSE = 'staff_reset';

    /**
     * The active Admin or Employee who owns this email, or null.
     */
    public function accountFor(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->whereIn('role', [UserRole::Admin, UserRole::Employee])
            ->where('is_active', true)
            ->first();
    }

    /**
     * Email a fresh code to the staff member.
     */
    public function send(User $user): string
    {
        $recentlySent = $user->otps()
            ->where('purpose', self::PURPOSE)
            ->where('created_at', '>', now()->subSeconds(ClientOtpService::RESEND_AFTER_SECONDS))
            ->exists();

        if ($recentlySent) {
            return ClientOtpService::TOO_SOON;
        }

        // Only the newest code works.
        $user->otps()->where('purpose', self::PURPOSE)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->otps()->create([
            'purpose' => self::PURPOSE,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(ClientOtpService::EXPIRES_IN_MINUTES),
        ]);

        $recipient = Setting::testModeEnabled() ? config('mail.test_recipient') : $user->email;

        Mail::to($recipient)->send(new StaffOtpMail($user, $code));

        return ClientOtpService::SENT;
    }

    /**
     * Check a code the staff member typed. A correct code is used up; a wrong one counts as a try.
     */
    public function verify(User $user, string $code): bool
    {
        /** @var Otp|null $otp */
        $otp = $user->otps()
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', ClientOtpService::MAX_ATTEMPTS)
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
}
