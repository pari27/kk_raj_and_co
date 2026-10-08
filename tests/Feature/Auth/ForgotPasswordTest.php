<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Forgot password?');
    }

    public function test_email_is_required(): void
    {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_valid(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_submitting_shows_a_confirmation_message(): void
    {
        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'someone@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        $this->followingRedirects()
            ->get(route('password.request'))
            ->assertSee(session('status'));
    }

    public function test_no_mail_is_sent_yet(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'email' => 'someone@example.com',
        ]);

        Mail::assertNothingSent();
    }
}
