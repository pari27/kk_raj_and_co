<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke tests for the Phase 1 UI-only pass: every screen should render (HTTP 200)
 * with no Blade/view errors. These are not feature tests for business logic —
 * the routes behind these views are still closures returning static-data views.
 */
class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array<int, string>>
     */
    public static function adminRoutes(): array
    {
        return [
            ['dashboard'],
            ['admin.services.index'],
            ['admin.services.create'],
            ['admin.designations.index'],
            ['admin.employees.index'],
            ['admin.employees.create'],
            ['admin.admin-accounts.index'],
            ['admin.admin-accounts.create'],
            ['admin.audit-log'],
            ['admin.settings.firm-profile'],
            ['admin.settings.email-templates.index'],
            ['customers.index'],
            ['customers.create'],
            ['enquiries.index'],
            ['enquiries.create'],
            ['tickets.index'],
            ['payments.index'],
            ['reports.index'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function adminRoutesWithParam(): array
    {
        return [
            ['admin.admin-accounts.edit'],
            ['admin.settings.email-templates.edit'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_can_view_admin_screen(string $routeName): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($user)->get(route($routeName))->assertOk();
    }

    #[DataProvider('adminRoutesWithParam')]
    public function test_admin_can_view_admin_screen_with_param(string $routeName): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($user)->get(route($routeName, 1))->assertOk();
    }

    public function test_admin_can_view_an_employees_show_and_edit_screens(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))->assertOk();
        $this->actingAs($admin)->get(route('admin.employees.edit', $employee))->assertOk();
    }

    public function test_admin_can_view_customer_enquiry_and_ticket_show_and_edit_screens(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        $ticket = Ticket::factory()->create([
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($admin)->get(route('customers.show', $customer))->assertOk();
        $this->actingAs($admin)->get(route('customers.edit', $customer))->assertOk();
        $this->actingAs($admin)->get(route('enquiries.show', $enquiry))->assertOk();
        $this->actingAs($admin)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_employee_can_view_their_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_employee_cannot_view_admin_only_masters(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($user)->get(route('admin.services.index'))->assertForbidden();
    }

    public function test_employee_can_view_shared_screens(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('tickets.index'))->assertOk();
        $this->actingAs($user)->get(route('enquiries.create'))->assertOk();
    }

    public function test_customer_portal_auth_screens_render_for_guests(): void
    {
        $this->get(route('customer.login'))->assertOk();
        $this->get(route('customer.verify-otp'))->assertOk();
        $this->get(route('customer.set-password'))->assertOk();
    }

    public function test_customer_portal_screens_render(): void
    {
        $this->get(route('customer.dashboard'))->assertOk();
        $this->get(route('customer.tickets.index'))->assertOk();
        $this->get(route('customer.tickets.show', 1))->assertOk();
    }
}
