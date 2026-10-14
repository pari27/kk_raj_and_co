<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * One client with one enquiry holding two services: one assigned to the
     * employee, one assigned to a colleague.
     *
     * @return array{User, Customer, Enquiry, Ticket, Ticket}
     */
    private function scenario(): array
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $colleague = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $customer = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id, 'total' => 5000]);

        $mine = Ticket::factory()->create([
            'enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $employee->id,
            'service_id' => Service::factory()->create(['name' => 'My Own Service'])->id,
        ]);
        $theirs = Ticket::factory()->create([
            'enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $colleague->id,
            'service_id' => Service::factory()->create(['name' => 'Colleague Service'])->id,
        ]);

        return [$employee, $customer, $enquiry, $mine, $theirs];
    }

    public function test_employee_cannot_open_a_service_assigned_to_someone_else(): void
    {
        [$employee, , , $mine, $theirs] = $this->scenario();

        $this->actingAs($employee)->get(route('tickets.show', $mine))->assertOk();
        $this->actingAs($employee)->get(route('tickets.show', $theirs))->assertForbidden();
    }

    public function test_enquiry_page_lists_only_the_employees_own_services(): void
    {
        [$employee, , $enquiry] = $this->scenario();

        $this->actingAs($employee)->get(route('enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('My Own Service')
            ->assertDontSee('Colleague Service');
    }

    public function test_client_page_lists_only_the_employees_own_services(): void
    {
        [$employee, $customer] = $this->scenario();

        $this->actingAs($employee)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('My Own Service')
            ->assertDontSee('Colleague Service');
    }

    public function test_admin_still_sees_every_service(): void
    {
        [, $customer, $enquiry] = $this->scenario();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('enquiries.show', $enquiry))
            ->assertSee('My Own Service')
            ->assertSee('Colleague Service');
        $this->actingAs($admin)->get(route('customers.show', $customer))
            ->assertSee('Colleague Service');
    }

    public function test_payments_page_hides_enquiries_the_employee_has_no_service_in(): void
    {
        [$employee] = $this->scenario();
        $otherEnquiry = Enquiry::factory()->create(['total' => 9999, 'number' => 'ENQ-2026-7777']);
        Ticket::factory()->create(['enquiry_id' => $otherEnquiry->id, 'assigned_to' => null]);
        Payment::factory()->create(['enquiry_id' => $otherEnquiry->id, 'reference' => 'HIDDEN-REF']);

        $this->actingAs($employee)->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('ENQ-2026-7777')
            ->assertDontSee('HIDDEN-REF');
    }

    public function test_employee_cannot_record_a_payment_on_an_enquiry_without_their_service(): void
    {
        [$employee] = $this->scenario();
        $other = Enquiry::factory()->create(['total' => 1000]);
        Ticket::factory()->create(['enquiry_id' => $other->id, 'assigned_to' => null]);

        $this->actingAs($employee)->post(route('payments.store'), [
            'enquiry_id' => $other->id,
            'amount' => 100,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ])->assertForbidden();

        $this->assertSame(0, Payment::count());
    }
}
