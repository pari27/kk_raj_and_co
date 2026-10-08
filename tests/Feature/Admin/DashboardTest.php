<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Enquiries vs completed tickets');
        $response->assertSee('Open tickets by status');
        $response->assertSee('Fees by month');
        $response->assertSee('Fee collection');
        $response->assertSee('Employee workload');
        $response->assertSee('Tickets by service');
    }

    public function test_admin_sees_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Enquiries vs completed tickets');
    }

    public function test_employee_sees_their_own_dashboard_at_the_same_url(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My tickets');
        $response->assertDontSee('Enquiries vs completed tickets');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_is_redirected_to_the_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_employee_is_redirected_to_the_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Employee,
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }
}
