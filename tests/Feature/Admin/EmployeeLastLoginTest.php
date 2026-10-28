<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLastLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_employee_page_shows_their_last_sign_in(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        AuditLog::create(['user_id' => $employee->id, 'action' => 'Logged in', 'module' => 'Login', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0']);

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Last login')
            ->assertSee('Today,')
            ->assertSee('Firefox · Windows')
            ->assertDontSee('Has not signed in yet');
    }

    public function test_it_says_so_when_the_employee_has_never_signed_in(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Has not signed in yet');
    }
}
