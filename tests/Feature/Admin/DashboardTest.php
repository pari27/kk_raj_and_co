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
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Needs attention');
        $response->assertSee('Open services by status');
        $response->assertSee('Recent enquiries');
        $response->assertSee('Staff workload');
        $response->assertSee('Recent payments');
    }

    public function test_the_classic_admin_dashboard_can_be_switched_back_on(): void
    {
        config(['app.admin_dashboard_design' => 'classic']);
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Enquiries vs completed services');
        $response->assertSee('Employee workload');
    }

    public function test_admin_sees_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Needs attention');
    }

    public function test_employee_sees_their_own_dashboard_at_the_same_url(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My services');
        $response->assertDontSee('Enquiries vs completed services');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_is_redirected_to_the_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
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
