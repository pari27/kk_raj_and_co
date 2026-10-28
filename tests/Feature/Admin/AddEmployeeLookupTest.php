<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddEmployeeLookupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    }

    public function test_the_add_employee_page_asks_for_the_mobile_number_first(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.employees.create'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'tm-photo-dropzone'), strpos($html, 'id="mobileInput"'));
        $this->assertLessThan(strpos($html, 'name="name"'), strpos($html, 'id="mobileInput"'));
        $this->assertStringContainsString('id="employeeLookupBanner"', $html);
        $this->assertStringContainsString('id="employeeSubmit"', $html);
    }

    public function test_the_lookup_returns_an_existing_employee_with_a_link_to_their_page(): void
    {
        $profile = Employee::factory()->create(['mobile' => '9876543210', 'gender' => 'Female']);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Priya Das', 'email' => 'priya@firm.com', 'employee_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->getJson(route('admin.employees.lookup', ['phone' => '9876543210']))
            ->assertOk()
            ->assertJsonPath('employee.name', 'Priya Das')
            ->assertJsonPath('employee.email', 'priya@firm.com')
            ->assertJsonPath('employee.gender', 'Female')
            ->assertJsonPath('employee.url', route('admin.employees.show', $employee));
    }

    public function test_the_lookup_finds_nothing_for_a_new_number_or_a_bad_one(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson(route('admin.employees.lookup', ['phone' => '9123456789']))->assertOk()->assertJsonPath('employee', null);
        $this->actingAs($admin)->getJson(route('admin.employees.lookup', ['phone' => '12']))->assertOk()->assertJsonPath('employee', null);
    }

    public function test_only_an_admin_can_use_the_lookup(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($employee)->getJson(route('admin.employees.lookup', ['phone' => '9876543210']))->assertForbidden();
    }
}
