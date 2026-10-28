<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Mail\StaffOtpMail;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function staff(UserRole $role = UserRole::Employee, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Priya Das',
            'email' => 'priya@firm.com',
            'role' => $role,
            'is_active' => true,
        ], $attributes));
    }

    /** The six digits that were emailed. */
    private function sentCode(): string
    {
        $code = null;
        Mail::assertSent(StaffOtpMail::class, function (StaffOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('Send me a code')
            ->assertSee('6-digit code');
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        $this->post(route('password.email'), [])->assertSessionHasErrors('email');
        $this->post(route('password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    }

    public function test_an_employee_gets_a_code_and_the_countdown_starts(): void
    {
        Mail::fake();
        $employee = $this->staff();

        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'priya@firm.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status')
            ->assertSessionHas('otp_expires_at');

        Mail::assertSent(StaffOtpMail::class, fn (StaffOtpMail $mail) => $mail->hasTo('priya@firm.com'));
        $this->assertSame('staff_reset', Otp::query()->firstOrFail()->purpose);
        $this->assertTrue($employee->otps()->exists());
    }

    public function test_an_admin_can_get_a_code_too(): void
    {
        Mail::fake();
        $this->staff(UserRole::Admin, ['email' => 'boss@firm.com']);

        $this->post(route('password.email'), ['email' => 'boss@firm.com']);

        Mail::assertSent(StaffOtpMail::class, fn (StaffOtpMail $mail) => $mail->hasTo('boss@firm.com'));
    }

    public function test_the_super_admin_inactive_and_unknown_accounts_get_no_email_but_the_same_reply(): void
    {
        Mail::fake();
        $this->staff(UserRole::SuperAdmin, ['email' => 'super@netiapps.com']);
        $this->staff(UserRole::Employee, ['email' => 'gone@firm.com', 'is_active' => false]);

        foreach (['super@netiapps.com', 'gone@firm.com', 'nobody@firm.com'] as $email) {
            $this->post(route('password.email'), ['email' => $email])->assertSessionHas('status');
        }

        Mail::assertNothingSent();
    }

    public function test_the_test_mode_recipient_gets_the_code_while_test_mode_is_on(): void
    {
        Mail::fake();
        $this->staff();
        Setting::current()->update(['test_mode' => true]);
        config(['mail.test_recipient' => 'tester@firm.com']);

        $this->post(route('password.email'), ['email' => 'priya@firm.com']);

        Mail::assertSent(StaffOtpMail::class, fn (StaffOtpMail $mail) => $mail->hasTo('tester@firm.com'));
    }

    public function test_no_code_is_sent_when_email_is_switched_off(): void
    {
        Mail::fake();
        $this->staff();
        Setting::current()->update(['notifications_enabled' => false]);

        $this->post(route('password.email'), ['email' => 'priya@firm.com'])->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_a_new_code_cannot_be_requested_again_within_a_minute(): void
    {
        Mail::fake();
        $this->staff();

        $this->post(route('password.email'), ['email' => 'priya@firm.com']);
        $this->post(route('password.email'), ['email' => 'priya@firm.com']);

        Mail::assertSent(StaffOtpMail::class, 1);
    }

    public function test_the_right_code_leads_to_a_new_password_and_works_only_once(): void
    {
        Mail::fake();
        $employee = $this->staff();

        $this->post(route('password.email'), ['email' => 'priya@firm.com']);
        $code = $this->sentCode();

        $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => $code])
            ->assertRedirect(route('password.reset'));

        $this->get(route('password.reset'))->assertOk()->assertSee('Reset your password');

        $this->post(route('password.reset.store'), [
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecret123', $employee->fresh()->password));

        // The same code can't be used a second time.
        $this->flushSession();
        $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_a_wrong_code_is_refused_and_keeps_the_countdown(): void
    {
        Mail::fake();
        $this->staff();
        $this->post(route('password.email'), ['email' => 'priya@firm.com']);

        $this->withSession(['otp_expires_at' => now()->addMinutes(5)->timestamp])
            ->from(route('password.request'))
            ->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => '000000'])
            ->assertSessionHasErrors('code')
            ->assertSessionHas('otp_expires_at');

        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    }

    public function test_an_expired_code_is_refused(): void
    {
        Mail::fake();
        $this->staff();
        $this->post(route('password.email'), ['email' => 'priya@firm.com']);
        $code = $this->sentCode();

        Otp::query()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_five_wrong_tries_kill_the_code(): void
    {
        Mail::fake();
        $this->staff();
        $this->post(route('password.email'), ['email' => 'priya@firm.com']);
        $code = $this->sentCode();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => '000000']);
        }

        $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_the_new_password_page_needs_a_verified_code_first(): void
    {
        $this->get(route('password.reset'))->assertRedirect(route('password.request'));

        $this->post(route('password.reset.store'), [
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect(route('password.request'));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        Mail::fake();
        $employee = $this->staff();
        $oldHash = $employee->password;

        $this->post(route('password.email'), ['email' => 'priya@firm.com']);
        $this->post(route('password.verify'), ['email' => 'priya@firm.com', 'code' => $this->sentCode()]);

        $this->post(route('password.reset.store'), ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post(route('password.reset.store'), ['password' => 'NewSecret123', 'password_confirmation' => 'Different123'])
            ->assertSessionHasErrors('password');

        $this->assertSame($oldHash, $employee->fresh()->password);
    }
}
