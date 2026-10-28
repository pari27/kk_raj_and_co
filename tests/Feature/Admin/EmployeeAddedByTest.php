<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAddedByTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_employee_page_shows_who_added_them_and_when(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'name' => 'Paramita']);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Added by')
            ->assertSee('Paramita')
            ->assertSee('Added on')
            ->assertSee($employee->created_at->format('j M Y'));
    }

    public function test_it_says_so_when_who_added_them_was_never_recorded(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'created_by' => null]);

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Not recorded');
    }

    public function test_a_new_employee_remembers_who_added_them(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $designation = \App\Models\EmployeeDesignation::factory()->create();

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '9123456780',
            'email' => 'neha@firm.com',
            'designation_id' => $designation->id,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame($admin->id, User::where('email', 'neha@firm.com')->firstOrFail()->created_by);
    }
}
