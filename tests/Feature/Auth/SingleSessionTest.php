<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Services\SingleSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'priya@firm.com',
            'role' => UserRole::Employee,
            'is_active' => true,
            'password' => 'RightPassword1',
        ], $attributes));
    }

    private function client(): Customer
    {
        return Customer::factory()->create([
            'name' => 'Meera Traders',
            'email' => 'meera@example.com',
            'is_active' => true,
            'password' => 'ClientPassword1',
        ]);
    }

    /** Start over as if a different browser or device were making the next request. */
    private function newDevice(): static
    {
        $this->flushSession();
        Auth::forgetGuards();

        return $this;
    }

    private function signInStaff(): void
    {
        $this->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_signing_in_claims_the_account(): void
    {
        $user = $this->staff();

        $this->signInStaff();

        $this->assertNotNull($user->fresh()->login_token);
        $this->assertNotNull($user->fresh()->session_seen_at);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_a_second_device_is_asked_before_it_takes_over(): void
    {
        $user = $this->staff();
        $this->signInStaff();
        $firstToken = $user->fresh()->login_token;

        $this->newDevice()
            ->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('login'));

        // Not signed in yet, and the first device still owns the account.
        $this->assertGuest();
        $this->assertSame($firstToken, $user->fresh()->login_token);

        // The question is asked on top of the normal login form.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('This account is already logged in on another device. Do you want to log out from there and log in here?')
            ->assertSee('data-shown="1"', false)
            ->assertSee('Remember me');

        // The message is shown once; a fresh visit to the page is clean.
        $this->get(route('login'))->assertSee('data-shown="0"', false);
    }

    public function test_the_second_device_can_take_over_and_the_first_is_signed_out_with_a_message(): void
    {
        $user = $this->staff();
        $this->signInStaff();
        $firstToken = $user->fresh()->login_token;

        $this->newDevice()->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('login'));
        // Logging in again, now knowing the other device will be signed out, takes over.
        $this->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('dashboard'));

        $secondToken = $user->fresh()->login_token;
        $this->assertNotNull($secondToken);
        $this->assertNotSame($firstToken, $secondToken);
        $this->get(route('dashboard'))->assertOk();

        // The first device's next click sends it to the login page with the message.
        $this->newDevice()
            ->actingAs($user->fresh())
            ->withSession(['login_token_web' => $firstToken])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('session_notice', SingleSession::REPLACED_MESSAGE);

        // And a page that is just sitting open learns it from the check it makes every few seconds.
        $this->newDevice()
            ->actingAs($user->fresh())
            ->withSession(['login_token_web' => $firstToken])
            ->getJson(route('session.ping'))
            ->assertUnauthorized()
            ->assertJson(['replaced' => true, 'login_url' => route('login')]);

        // Being replaced does not sign the newer device out.
        $this->assertSame($secondToken, $user->fresh()->login_token);
    }

    public function test_the_login_page_shows_the_message_after_being_replaced(): void
    {
        $this->withSession(['session_notice' => SingleSession::REPLACED_MESSAGE])
            ->get(route('login'))
            ->assertSee('someone logged in to this account from another device');
    }

    public function test_signing_out_frees_the_account(): void
    {
        $user = $this->staff();
        $this->signInStaff();

        $this->post(route('logout'));

        $this->assertNull($user->fresh()->login_token);

        $this->newDevice();
        $this->signInStaff();
    }

    public function test_a_device_that_has_gone_quiet_no_longer_blocks_a_new_sign_in(): void
    {
        $user = $this->staff();
        $this->signInStaff();

        $user->newQuery()->whereKey($user->id)->update(['session_seen_at' => now()->subMinutes(SingleSession::STALE_AFTER_MINUTES + 1)]);

        $this->newDevice();
        $this->signInStaff();
    }

    public function test_a_wrong_password_never_reaches_the_question(): void
    {
        $this->staff();
        $this->signInStaff();

        $this->newDevice()
            ->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'WrongPassword1'])
            ->assertSessionHasErrors('email');
    }

    public function test_the_question_only_appears_after_a_correct_password_for_a_busy_account(): void
    {
        $this->get(route('login'))->assertSee('data-shown="0"', false);
        $this->assertGuest();
    }

    public function test_the_super_admin_and_admin_follow_the_same_rule(): void
    {
        foreach ([UserRole::SuperAdmin, UserRole::Admin] as $i => $role) {
            $user = $this->staff(['email' => "boss{$i}@firm.com", 'role' => $role]);

            $this->post(route('login.store'), ['email' => "boss{$i}@firm.com", 'password' => 'RightPassword1'])
                ->assertRedirect(route('dashboard'));
            $this->newDevice()
                ->post(route('login.store'), ['email' => "boss{$i}@firm.com", 'password' => 'RightPassword1'])
                ->assertRedirect(route('login'));
            $this->newDevice();
        }
    }

    public function test_clients_follow_the_same_rule(): void
    {
        $client = $this->client();
        $login = ['email' => 'meera@example.com', 'password' => 'ClientPassword1'];

        $this->post(route('customer.login.store'), $login)->assertRedirect(route('customer.dashboard'));
        $firstToken = $client->fresh()->login_token;
        $this->assertNotNull($firstToken);

        $this->newDevice()
            ->post(route('customer.login.store'), $login)
            ->assertRedirect(route('customer.login'));
        $this->assertGuest('customer');

        $this->get(route('customer.login'))->assertOk()->assertSee('data-shown="1"', false);

        $this->post(route('customer.login.store'), $login)->assertRedirect(route('customer.dashboard'));
        $this->assertNotSame($firstToken, $client->fresh()->login_token);
        $this->get(route('customer.dashboard'))->assertOk();

        $this->newDevice()
            ->actingAs($client->fresh(), 'customer')
            ->withSession(['login_token_customer' => $firstToken])
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('customer.login'))
            ->assertSessionHas('session_notice', SingleSession::REPLACED_MESSAGE);
    }

    public function test_the_login_page_can_ask_whether_an_account_is_signed_in_elsewhere(): void
    {
        $user = $this->staff();

        $this->getJson(route('login.session-check', ['email' => 'priya@firm.com']))->assertOk()->assertJson(['busy' => false]);

        $this->signInStaff();

        $this->newDevice()
            ->getJson(route('login.session-check', ['email' => 'PRIYA@firm.com']))
            ->assertOk()
            ->assertJson(['busy' => true]);

        // Unknown emails and an empty box get the same plain "no".
        $this->getJson(route('login.session-check', ['email' => 'nobody@firm.com']))->assertJson(['busy' => false]);
        $this->getJson(route('login.session-check'))->assertJson(['busy' => false]);

        // A device that has gone quiet does not count.
        $user->newQuery()->whereKey($user->id)->update(['session_seen_at' => now()->subMinutes(SingleSession::STALE_AFTER_MINUTES + 1)]);
        $this->getJson(route('login.session-check', ['email' => 'priya@firm.com']))->assertJson(['busy' => false]);
    }

    public function test_the_client_login_page_can_ask_the_same_thing(): void
    {
        $client = $this->client();

        $this->getJson(route('customer.login.session-check', ['email' => 'meera@example.com']))->assertJson(['busy' => false]);

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'ClientPassword1']);

        $this->newDevice()
            ->getJson(route('customer.login.session-check', ['email' => 'meera@example.com']))
            ->assertJson(['busy' => true]);
    }

    public function test_the_login_pages_carry_the_warning_ready_to_show(): void
    {
        $this->get(route('login'))
            ->assertSee('id="sessionBusy"', false)
            ->assertSee('login\/session-check', false);

        $this->get(route('customer.login'))
            ->assertSee('id="sessionBusy"', false)
            ->assertSee('client\/login\/session-check', false);
    }

    public function test_the_session_check_is_limited_to_twenty_a_minute(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->getJson(route('login.session-check', ['email' => 'a@b.com']))->assertOk();
        }

        $this->getJson(route('login.session-check', ['email' => 'a@b.com']))->assertStatus(429);
    }
    public function test_after_the_page_has_shown_the_warning_the_first_sign_in_goes_straight_through(): void
    {
        $user = $this->staff();
        $this->signInStaff();
        $firstToken = $user->fresh()->login_token;

        // The login page shows the warning as soon as the email is typed...
        $this->newDevice()
            ->getJson(route('login.session-check', ['email' => 'priya@firm.com']))
            ->assertJson(['busy' => true]);

        // ...so the very next sign-in takes over without a second try.
        $this->post(route('login.store'), ['email' => 'priya@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('dashboard'));
        $this->assertNotSame($firstToken, $user->fresh()->login_token);
    }

    public function test_the_same_goes_for_clients(): void
    {
        $client = $this->client();
        $login = ['email' => 'meera@example.com', 'password' => 'ClientPassword1'];
        $this->post(route('customer.login.store'), $login);
        $firstToken = $client->fresh()->login_token;

        $this->newDevice()->getJson(route('customer.login.session-check', ['email' => 'meera@example.com']))->assertJson(['busy' => true]);

        $this->post(route('customer.login.store'), $login)->assertRedirect(route('customer.dashboard'));
        $this->assertNotSame($firstToken, $client->fresh()->login_token);
    }

    public function test_a_warning_for_one_email_does_not_cover_another_email(): void
    {
        $this->staff();
        $this->staff(['email' => 'other@firm.com']);
        $this->signInStaff();
        $this->newDevice()->post(route('login.store'), ['email' => 'other@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('dashboard'));
        $this->newDevice();

        $this->getJson(route('login.session-check', ['email' => 'priya@firm.com']))->assertJson(['busy' => true]);

        // Warned about priya, not about other@firm.com.
        $this->post(route('login.store'), ['email' => 'other@firm.com', 'password' => 'RightPassword1'])
            ->assertRedirect(route('login'));
    }
    public function test_right_after_being_signed_out_the_page_shows_only_the_signed_out_message(): void
    {
        $this->withSession(['session_notice' => SingleSession::REPLACED_MESSAGE])
            ->get(route('login'))
            ->assertSee('someone logged in to this account from another device')
            ->assertSee('data-after-kick="1"', false);

        $this->flushSession();
        $this->get(route('login'))->assertSee('data-after-kick="0"', false);
    }
    public function test_the_check_pages_make_answers_ok_when_nobody_is_signed_in(): void
    {
        $this->getJson(route('session.ping'))->assertOk();
    }

    public function test_an_account_with_no_token_yet_adopts_the_session_it_is_first_seen_in(): void
    {
        $user = $this->staff();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertNotNull($user->fresh()->login_token);
    }
}
