<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockScreenTest extends TestCase
{
    use RefreshDatabase;

    private function staff(UserRole $role = UserRole::Employee): User
    {
        return User::factory()->create([
            'name' => 'Priya Das',
            'email' => 'priya@firm.com',
            'role' => $role,
            'is_active' => true,
            'password' => 'RightPassword1',
        ]);
    }

    public function test_the_lock_screen_is_on_every_staff_page(): void
    {
        $this->actingAs($this->staff())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Locked after 20 minutes of no activity')
            ->assertSee('Welcome back, Priya Das')
            ->assertSee('priya@firm.com')
            ->assertSee('Not you? Log out');
    }

    public function test_the_right_password_unlocks(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('lock.unlock'), ['password' => 'RightPassword1'])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_a_wrong_password_does_not_unlock(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('lock.unlock'), ['password' => 'WrongPassword1'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'That password is not right. Please try again.');
    }

    public function test_a_password_is_required(): void
    {
        $this->actingAs($this->staff())->postJson(route('lock.unlock'), [])->assertStatus(422);
    }

    public function test_someone_who_is_signed_out_cannot_use_the_unlock_check(): void
    {
        $this->postJson(route('lock.unlock'), ['password' => 'RightPassword1'])->assertUnauthorized();
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

    public function test_the_lock_screen_is_on_every_client_portal_page(): void
    {
        $this->actingAs($this->client(), 'customer')->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Locked after 20 minutes of no activity')
            ->assertSee('Welcome back, Meera Traders')
            ->assertSee('meera@example.com')
            ->assertSee(route('customer.lock.unlock'), false);
    }

    public function test_a_client_unlocks_with_their_own_password_only(): void
    {
        $client = $this->client();

        $this->actingAs($client, 'customer')
            ->postJson(route('customer.lock.unlock'), ['password' => 'ClientPassword1'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($client, 'customer')
            ->postJson(route('customer.lock.unlock'), ['password' => 'WrongPassword1'])
            ->assertStatus(422);
    }

    public function test_a_staff_password_does_not_unlock_a_client_and_signed_out_visitors_are_refused(): void
    {
        $this->postJson(route('customer.lock.unlock'), ['password' => 'ClientPassword1'])->assertUnauthorized();

        // A signed-in staff member has no client session, so the client check refuses them.
        $this->actingAs($this->staff())
            ->postJson(route('customer.lock.unlock'), ['password' => 'RightPassword1'])
            ->assertUnauthorized();
    }

    public function test_the_unlock_check_is_limited_to_ten_tries_a_minute(): void
    {
        $this->actingAs($this->staff());

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('lock.unlock'), ['password' => 'WrongPassword1'])->assertStatus(422);
        }

        $this->postJson(route('lock.unlock'), ['password' => 'RightPassword1'])->assertStatus(429);
    }
}
