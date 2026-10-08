<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_enquiry_and_assign_tickets_to_employees(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create();
        $serviceA = Service::factory()->create(['default_price' => 2500, 'gst_percent' => 18, 'price_includes_gst' => false]);
        $serviceB = Service::factory()->create(['default_price' => 500, 'gst_percent' => 18, 'price_includes_gst' => false]);
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'notes' => 'Client needs this urgently',
            'discount' => 100,
            'services' => [
                ['service_id' => $serviceA->id, 'assigned_to' => $employee->id],
                ['service_id' => $serviceB->id],
            ],
        ]);

        $enquiry = Enquiry::first();
        $response->assertRedirect(route('enquiries.show', $enquiry));

        $this->assertNotNull($enquiry);
        $this->assertSame($customer->id, $enquiry->customer_id);
        $this->assertSame('Client needs this urgently', $enquiry->notes);
        $this->assertSame('100.00', (string) $enquiry->discount);
        $this->assertSame(2, $enquiry->tickets()->count());

        $ticketA = Ticket::where('service_id', $serviceA->id)->firstOrFail();
        $this->assertSame($employee->id, $ticketA->assigned_to);
        $this->assertSame('2500.00', (string) $ticketA->price);
        $this->assertSame('450.00', (string) $ticketA->gst_amount);
        $this->assertSame('2950.00', (string) $ticketA->total);

        $ticketB = Ticket::where('service_id', $serviceB->id)->firstOrFail();
        $this->assertNull($ticketB->assigned_to);

        // subtotal 3000 + gst 540 - discount 100 = 3440
        $this->assertSame('3440.00', (string) $enquiry->total);
    }

    public function test_admin_can_create_an_enquiry_for_a_brand_new_client(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_phone' => '9123456780',
            'customer_name' => 'Sharma Tea Traders',
            'customer_email' => 'sharma@example.com',
            'customer_email_notifications' => '1',
            'services' => [['service_id' => $service->id]],
        ]);

        $customer = Customer::where('phone', '9123456780')->firstOrFail();
        $response->assertRedirect(route('enquiries.show', Enquiry::first()));

        $this->assertSame('Sharma Tea Traders', $customer->name);
        $this->assertSame('sharma@example.com', $customer->email);
        $this->assertTrue($customer->email_notifications_enabled);
        $this->assertSame($customer->id, Enquiry::first()->customer_id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Customer',
            'action' => 'Created',
            'subject_id' => $customer->id,
        ]);
    }

    public function test_an_existing_clients_notification_preference_is_updated_on_save(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create(['email' => 'client@example.com', 'email_notifications_enabled' => false]);
        $service = Service::factory()->create();

        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'customer_email_notifications' => '1',
            'services' => [['service_id' => $service->id]],
        ]);

        $this->assertTrue($customer->refresh()->email_notifications_enabled);
    }

    public function test_an_existing_clients_missing_email_can_be_backfilled_on_save(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create(['email' => null]);
        $service = Service::factory()->create();

        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'customer_email' => 'new-email@example.com',
            'services' => [['service_id' => $service->id]],
        ]);

        $this->assertSame('new-email@example.com', $customer->refresh()->email);
    }

    public function test_an_existing_clients_email_is_not_overwritten_if_already_set(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create(['email' => 'original@example.com']);
        $service = Service::factory()->create();

        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'customer_email' => 'different@example.com',
            'services' => [['service_id' => $service->id]],
        ]);

        $this->assertSame('original@example.com', $customer->refresh()->email);
    }

    public function test_cannot_create_a_new_client_with_a_phone_number_already_in_use(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Customer::factory()->create(['phone' => '9123456780']);
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_phone' => '9123456780',
            'customer_name' => 'Duplicate Phone Client',
            'services' => [['service_id' => $service->id]],
        ]);

        $response->assertSessionHasErrors('customer_phone');
        $this->assertSame(1, Customer::count());
    }

    public function test_without_a_customer_id_the_phone_and_name_are_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->post(route('enquiries.store'), [
            'services' => [['service_id' => $service->id]],
        ]);

        $response->assertSessionHasErrors(['customer_phone', 'customer_name']);
    }

    public function test_employee_creating_an_enquiry_defaults_every_ticket_to_themselves(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create(['default_price' => 1000, 'gst_percent' => 18]);

        $this->actingAs($employee)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'services' => [
                ['service_id' => $service->id, 'price' => 1],
            ],
        ]);

        $ticket = Ticket::first();

        $this->assertSame($employee->id, $ticket->assigned_to);
        // Price override is ignored for non-admin-like users regardless of assignment.
        $this->assertSame('1000.00', (string) $ticket->price);
    }

    public function test_employee_can_delegate_a_ticket_to_a_colleague(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $colleague = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($employee)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'services' => [
                ['service_id' => $service->id, 'assigned_to' => $colleague->id],
            ],
        ]);

        $ticket = Ticket::first();

        $this->assertSame($colleague->id, $ticket->assigned_to);
    }

    public function test_employee_cannot_set_a_discount_or_override_price(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create(['default_price' => 1000, 'gst_percent' => 18]);

        $this->actingAs($employee)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'discount' => 500,
            'services' => [
                ['service_id' => $service->id, 'price' => 1],
            ],
        ]);

        $enquiry = Enquiry::first();

        $this->assertSame('0.00', (string) $enquiry->discount);
    }

    public function test_creating_an_enquiry_writes_audit_log_entries_for_the_enquiry_and_each_ticket(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'services' => [
                ['service_id' => $service->id],
            ],
        ]);

        $this->assertSame(1, AuditLog::where('module', 'Enquiry')->count());
        $this->assertSame(1, AuditLog::where('module', 'Ticket')->count());
    }

    public function test_at_least_one_service_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'services' => [],
        ]);

        $response->assertSessionHasErrors('services');
        $this->assertSame(0, Enquiry::count());
    }

    public function test_employee_only_sees_enquiries_with_a_ticket_assigned_to_them(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();

        $myEnquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        Ticket::factory()->create(['enquiry_id' => $myEnquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $employee->id]);

        $othersEnquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        Ticket::factory()->create(['enquiry_id' => $othersEnquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $other->id]);

        $response = $this->actingAs($employee)->get(route('enquiries.index'));

        $response->assertOk();
        $response->assertSee($myEnquiry->number);
        $response->assertDontSee($othersEnquiry->number);
    }

    public function test_admin_sees_every_enquiry_regardless_of_assignment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();

        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $employee->id]);

        $response = $this->actingAs($admin)->get(route('enquiries.index'));

        $response->assertOk();
        $response->assertSee($enquiry->number);
    }

    public function test_employee_cannot_view_an_enquiry_with_no_ticket_assigned_to_them(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();

        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $other->id]);

        $this->actingAs($employee)->get(route('enquiries.show', $enquiry))->assertForbidden();
    }

    public function test_employee_can_view_an_enquiry_with_a_ticket_assigned_to_them(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create();

        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $employee->id]);

        $this->actingAs($employee)->get(route('enquiries.show', $enquiry))->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('enquiries.index'))->assertRedirect(route('login'));
    }
}
