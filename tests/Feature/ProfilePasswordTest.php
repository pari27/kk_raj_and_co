<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_the_change_password_screen(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertOk();
        $response->assertSee('Change Password');
    }

    public function test_user_can_update_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-strong-password', $user->refresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_must_meet_minimum_length(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_employee_can_also_change_their_password(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee, 'password' => 'old-password']);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-strong-password', $user->refresh()->password));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('password.change'))->assertRedirect(route('login'));
        $this->put(route('password.update'), [])->assertRedirect(route('login'));
    }

    public function test_recent_logins_lists_the_users_other_sessions(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '203.0.113.5',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/128.0 Safari/537.36',
            'payload' => 'irrelevant',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertOk();
        $response->assertSee('Chrome on Windows');
        $response->assertSee('203.0.113.5');
    }
}
