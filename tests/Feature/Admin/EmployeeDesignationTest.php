<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDesignationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_designations_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $response = $this->actingAs($admin)->get(route('admin.designations.index'));

        $response->assertOk();
        $response->assertSee('Tax Consultant');
    }

    public function test_admin_can_create_a_designation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.designations.store'), [
            'name' => 'Audit Manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.designations.index'));

        $designation = EmployeeDesignation::query()->where('name', 'Audit Manager')->firstOrFail();
        $this->assertTrue($designation->is_active);
    }

    public function test_is_active_defaults_to_false_when_not_submitted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.designations.store'), [
            'name' => 'Audit Manager',
        ]);

        $designation = EmployeeDesignation::query()->where('name', 'Audit Manager')->firstOrFail();
        $this->assertFalse($designation->is_active);
    }

    public function test_designation_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.designations.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, EmployeeDesignation::query()->count());
    }

    public function test_designation_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $response = $this->actingAs($admin)->post(route('admin.designations.store'), [
            'name' => 'Tax Consultant',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, EmployeeDesignation::query()->count());
    }

    public function test_admin_can_update_a_designation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $response = $this->actingAs($admin)->put(route('admin.designations.update', $designation), [
            'name' => 'Senior Tax Consultant',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.designations.index'));
        $this->assertSame('Senior Tax Consultant', $designation->refresh()->name);
    }

    public function test_updating_a_designation_can_keep_its_own_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $response = $this->actingAs($admin)->put(route('admin.designations.update', $designation), [
            'name' => 'Tax Consultant',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_updating_a_designation_name_to_another_designations_name_fails(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Article Assistant']);

        $response = $this->actingAs($admin)->put(route('admin.designations.update', $designation), [
            'name' => 'Tax Consultant',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('Article Assistant', $designation->refresh()->name);
    }

    public function test_admin_can_toggle_a_designation_active(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.designations.toggle-active', $designation));

        $response->assertRedirect(route('admin.designations.index'));
        $this->assertFalse($designation->refresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.designations.toggle-active', $designation));
        $this->assertTrue($designation->refresh()->is_active);
    }

    public function test_admin_can_delete_a_designation_with_no_employees(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $response = $this->actingAs($admin)->delete(route('admin.designations.destroy', $designation));

        $response->assertRedirect(route('admin.designations.index'));
        $this->assertTrue($designation->refresh()->is_deleted);
    }

    public function test_deleted_designation_is_hidden_from_the_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);

        $this->actingAs($admin)->delete(route('admin.designations.destroy', $designation));

        // The success flash message names the designation too, so make a second,
        // flash-free request before asserting it's gone from the table itself.
        $this->actingAs($admin)->get(route('admin.designations.index'));
        $response = $this->actingAs($admin)->get(route('admin.designations.index'));

        $response->assertOk();
        $response->assertDontSee('Tax Consultant');
    }

    public function test_admin_cannot_delete_a_designation_with_employees_assigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['name' => 'Tax Consultant']);
        Employee::factory()->create(['designation_id' => $designation->id]);

        $response = $this->actingAs($admin)->delete(route('admin.designations.destroy', $designation));

        $response->assertRedirect(route('admin.designations.index'));
        $this->assertFalse($designation->refresh()->is_deleted);
    }

    public function test_employee_cannot_view_or_manage_designations(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $designation = EmployeeDesignation::factory()->create();

        $this->actingAs($employee)->get(route('admin.designations.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('admin.designations.store'), ['name' => 'Should Not Save'])->assertForbidden();
        $this->actingAs($employee)->put(route('admin.designations.update', $designation), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($employee)->patch(route('admin.designations.toggle-active', $designation))->assertForbidden();
        $this->actingAs($employee)->delete(route('admin.designations.destroy', $designation))->assertForbidden();

        $this->assertSame(1, EmployeeDesignation::query()->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.designations.index'))->assertRedirect(route('login'));
    }
}
