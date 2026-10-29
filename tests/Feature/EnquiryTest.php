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
        $this->assertSame(1, AuditLog::where('module', 'Service')->count());
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

    // ------------------------------------------------------------------
    // Adding a service to an enquiry that is still open
    // ------------------------------------------------------------------

    private function openEnquiryWithOneService(float $discount = 0): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::factory()->create();
        $first = Service::factory()->create(['name' => 'GST Registration', 'default_price' => 1000, 'gst_percent' => 18, 'price_includes_gst' => false]);

        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'discount' => $discount,
            'services' => [['service_id' => $first->id]],
        ]);

        return [$admin, Enquiry::firstOrFail(), $customer];
    }

    public function test_an_admin_can_add_a_service_to_an_open_enquiry_and_the_fee_is_recalculated(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService(discount: 100);
        $this->assertEquals(1080.0, (float) $enquiry->total); // 1000 + 180 GST - 100 discount

        $pan = Service::factory()->create(['name' => 'PAN Registration', 'default_price' => 500, 'gst_percent' => 18, 'price_includes_gst' => false]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), [
            'service_id' => $pan->id,
            'assigned_to' => $employee->id,
        ])->assertRedirect(route('enquiries.show', $enquiry))->assertSessionHas('status');

        $enquiry->refresh();
        $this->assertSame(2, $enquiry->tickets()->count());
        $this->assertEquals(1500.0, (float) $enquiry->subtotal);
        $this->assertEquals(270.0, (float) $enquiry->gst_total);
        $this->assertEquals(100.0, (float) $enquiry->discount);   // the discount stays
        $this->assertEquals(1670.0, (float) $enquiry->total);     // 1500 + 270 - 100
        $this->assertSame('Open', $enquiry->status);

        $added = $enquiry->tickets()->where('service_id', $pan->id)->firstOrFail();
        $this->assertSame('Documents Pending', $added->status->value);
        $this->assertSame($employee->id, $added->assigned_to);
        $this->assertEquals(590.0, (float) $added->total);

        $this->assertTrue(AuditLog::where('module', 'Service')->where('record_label', $added->number)->exists());
        $this->assertTrue(AuditLog::where('module', 'Enquiry')->where('record_label', $enquiry->number)->where('details', 'like', '%Added service PAN Registration%')->exists());
    }

    public function test_a_price_can_be_changed_when_adding_a_service(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $pan = Service::factory()->create(['default_price' => 500, 'gst_percent' => 18, 'price_includes_gst' => false]);

        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id, 'price' => 800]);

        $added = $enquiry->tickets()->where('service_id', $pan->id)->firstOrFail();
        $this->assertEquals(800.0, (float) $added->price);
        $this->assertEquals(944.0, (float) $added->total);
        $this->assertEquals(1180.0 + 944.0, (float) $enquiry->fresh()->total);
    }

    public function test_the_client_is_asked_for_the_new_services_documents(): void
    {
        [$admin, $enquiry, $customer] = $this->openEnquiryWithOneService();
        $customer->update(['email' => 'meera@example.com', 'email_notifications_enabled' => true]);

        $pan = Service::factory()->create(['name' => 'PAN Registration']);
        \App\Models\ServiceDocument::factory()->create(['service_id' => $pan->id, 'name' => 'Aadhaar Card', 'is_mandatory' => true]);

        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id]);

        $mails = \Illuminate\Support\Facades\Mail::sent(\App\Mail\FormalNotificationMail::class, fn ($mail) => $mail->hasTo('meera@example.com'));
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('PAN Registration (', $mails->first()->messageBody);
        $this->assertStringContainsString('Aadhaar Card', $mails->first()->messageBody);
    }
    public function test_a_closed_enquiry_cannot_have_services_added(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $enquiry->update(['status' => 'Closed']);
        $pan = Service::factory()->create();

        $this->actingAs($admin)->get(route('enquiries.show', $enquiry))->assertDontSee('+ Add service');

        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id])->assertForbidden();
        $this->assertSame(1, $enquiry->tickets()->count());
    }

    public function test_the_add_button_shows_for_an_admin_on_an_open_enquiry_only(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $enquiry->tickets()->update(['assigned_to' => $employee->id]);
        $pan = Service::factory()->create();

        $this->actingAs($admin)->get(route('enquiries.show', $enquiry))
            ->assertSee('+ Add service')
            ->assertSee('Add a service')
            ->assertSee($enquiry->number.' &middot; '.e($enquiry->customer->name).' &middot; 1 service so far', false)
            ->assertSee('Shows how many open services each person has')
            ->assertSee('Add service');

        // An Employee can open the enquiry but cannot add to it.
        $this->actingAs($employee)->get(route('enquiries.show', $enquiry))->assertOk()->assertDontSee('+ Add service');
        $this->actingAs($employee)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id])->assertForbidden();
        $this->assertSame(1, $enquiry->tickets()->count());
    }

    public function test_only_an_available_service_can_be_added(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $retired = Service::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $retired->id])->assertSessionHasErrors('service_id');
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), [])->assertSessionHasErrors('service_id');
        $this->assertSame(1, $enquiry->tickets()->count());
    }

    public function test_an_enquiry_with_every_service_completed_but_not_paid_is_still_open_for_more_services(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $enquiry->tickets()->update(['status' => 'Task Completed', 'completed_at' => now()]);
        \App\Services\EnquiryStatusService::recompute($enquiry->fresh());
        $this->assertSame('Open', $enquiry->fresh()->status);

        $pan = Service::factory()->create();
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id])
            ->assertRedirect(route('enquiries.show', $enquiry));
        $this->assertSame(2, $enquiry->tickets()->count());
    }
    public function test_a_service_already_in_the_enquiry_cannot_be_added_again(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $existing = $enquiry->tickets()->firstOrFail()->service;
        $pan = Service::factory()->create(['name' => 'PAN Registration']);

        // The form offers only services that are not in the enquiry yet.
        $this->actingAs($admin)->get(route('enquiries.show', $enquiry))
            ->assertSee('PAN Registration')
            ->assertViewHas('addService', fn ($data) => ! collect($data['services'])->contains('id', $existing->id) && collect($data['services'])->contains('id', $pan->id));

        // Forcing it is refused.
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $existing->id])
            ->assertSessionHasErrors(['service_id' => 'This service is already in this enquiry.']);
        $this->assertSame(1, $enquiry->tickets()->count());

        // A different service still goes in, and then it too is used up.
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id])->assertSessionHasErrors('service_id');
        $this->assertSame(2, $enquiry->tickets()->count());
    }

    public function test_the_same_service_can_be_in_a_different_enquiry(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $other = Enquiry::factory()->create(['customer_id' => $enquiry->customer_id, 'status' => 'Open']);
        $existing = $enquiry->tickets()->firstOrFail()->service;

        $this->actingAs($admin)->post(route('enquiries.services.store', $other), ['service_id' => $existing->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $other->tickets()->count());
    }

    // ------------------------------------------------------------------
    // Deleting a service until a document has been submitted
    // ------------------------------------------------------------------

    /** An enquiry with two services: 1,180 and 590 with GST. @return array{0: User, 1: Enquiry, 2: Ticket, 3: Ticket} */
    private function enquiryWithTwoServices(float $discount = 0): array
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService($discount);
        $pan = Service::factory()->create(['name' => 'PAN Registration', 'default_price' => 500, 'gst_percent' => 18, 'price_includes_gst' => false]);
        $this->actingAs($admin)->post(route('enquiries.services.store', $enquiry), ['service_id' => $pan->id]);

        $first = $enquiry->tickets()->orderBy('id')->first();
        $second = $enquiry->tickets()->orderByDesc('id')->first();

        return [$admin, $enquiry->fresh(), $first, $second];
    }

    public function test_an_admin_can_delete_a_service_nobody_has_submitted_a_document_for_and_the_fee_is_recalculated(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices(discount: 100);
        $this->assertEquals(1670.0, (float) $enquiry->total); // 1180 + 590 - 100

        $this->actingAs($admin)->delete(route('tickets.destroy', $second))
            ->assertRedirect(route('enquiries.show', $enquiry))
            ->assertSessionHas('status');

        $this->assertNull(Ticket::find($second->id));
        $this->assertNotNull(Ticket::find($first->id));

        $enquiry->refresh();
        $this->assertEquals(1000.0, (float) $enquiry->subtotal);
        $this->assertEquals(180.0, (float) $enquiry->gst_total);
        $this->assertEquals(100.0, (float) $enquiry->discount);   // the discount stays
        $this->assertEquals(1080.0, (float) $enquiry->total);     // 1000 + 180 - 100

        $this->assertTrue(AuditLog::where('module', 'Service')->where('action', 'Deleted')->where('record_label', $second->number)->exists());
        $this->assertTrue(AuditLog::where('module', 'Enquiry')->where('details', 'like', '%Deleted service PAN Registration%')->exists());
    }

    public function test_a_service_with_a_submitted_document_cannot_be_deleted(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $doc = \App\Models\ServiceDocument::factory()->create(['service_id' => $second->service_id]);
        \App\Models\TicketDocument::factory()->create(['ticket_id' => $second->id, 'service_document_id' => $doc->id, 'status' => 'Awaiting check']);

        $this->actingAs($admin)->get(route('tickets.show', $second))
            ->assertSee('Documents have already been submitted for this service, so it can no longer be deleted.')
            ->assertSee('id="ticket-delete-panel"', false);

        $this->actingAs($admin)->delete(route('tickets.destroy', $second))
            ->assertRedirect(route('tickets.show', $second))
            ->assertSessionHas('error');

        $this->assertNotNull(Ticket::find($second->id));
        $this->assertSame(2, $enquiry->tickets()->count());
    }

    public function test_the_only_service_in_an_enquiry_cannot_be_deleted(): void
    {
        [$admin, $enquiry] = $this->openEnquiryWithOneService();
        $only = $enquiry->tickets()->firstOrFail();

        $this->actingAs($admin)->delete(route('tickets.destroy', $only))->assertSessionHas('error');

        $this->assertNotNull(Ticket::find($only->id));
        $this->actingAs($admin)->get(route('tickets.show', $only))->assertSee('only service in its enquiry');
    }

    public function test_a_service_cannot_be_deleted_if_payments_would_then_exceed_the_enquiry_total(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        \App\Models\Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 1500]);

        // Without the 590 service the enquiry would total 1,180, but 1,500 has been paid.
        $this->actingAs($admin)->delete(route('tickets.destroy', $second))->assertSessionHas('error');
        $this->assertNotNull(Ticket::find($second->id));

        // A smaller payment fits, so deleting is fine.
        \App\Models\Payment::query()->update(['amount' => 1000]);
        $this->actingAs($admin)->delete(route('tickets.destroy', $second))->assertSessionMissing('error');
        $this->assertNull(Ticket::find($second->id));
    }

    public function test_a_completed_service_cannot_be_deleted(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $second->update(['status' => 'Task Completed', 'completed_at' => now()]);

        $this->actingAs($admin)->delete(route('tickets.destroy', $second))->assertSessionHas('error');
        $this->assertNotNull(Ticket::find($second->id));
    }

    public function test_only_an_admin_can_delete_a_service(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $second->update(['assigned_to' => $employee->id]);

        $this->actingAs($employee)->get(route('tickets.show', $second))->assertOk()->assertDontSee('id="ticket-delete-panel"', false);
        $this->actingAs($employee)->delete(route('tickets.destroy', $second))->assertForbidden();
        $this->assertNotNull(Ticket::find($second->id));
    }

    public function test_the_delete_button_is_there_with_a_confirmation_when_a_service_can_go(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();

        $this->actingAs($admin)->get(route('tickets.show', $second))
            ->assertSee('A service can be deleted until a document is submitted for it.')
            ->assertSee('js-confirm-delete', false)
            ->assertSee('be undone.', false);
    }

    public function test_notifications_that_point_at_a_deleted_service_are_removed(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $link = route('tickets.show', $second);
        \App\Models\AppNotification::create(['user_id' => $admin->id, 'type' => 'tickets', 'title' => 'Service assigned', 'body' => 'x', 'url' => $link]);
        \App\Models\AppNotification::create(['user_id' => $admin->id, 'type' => 'tickets', 'title' => 'Keep me', 'body' => 'y', 'url' => route('tickets.show', $first)]);
        \App\Models\ClientNotification::create(['customer_id' => $enquiry->customer_id, 'type' => 'service', 'title' => 'Documents needed', 'url' => route('customer.tickets.show', $second)]);

        $this->actingAs($admin)->delete(route('tickets.destroy', $second));

        $this->assertSame(0, \App\Models\AppNotification::where('url', $link)->count());
        $this->assertSame(1, \App\Models\AppNotification::where('title', 'Keep me')->count());
        $this->assertSame(0, \App\Models\ClientNotification::where('title', 'Documents needed')->count());
    }
    public function test_the_enquiry_page_has_a_delete_icon_on_each_service_that_can_go(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $doc = \App\Models\ServiceDocument::factory()->create(['service_id' => $first->service_id]);
        \App\Models\TicketDocument::factory()->create(['ticket_id' => $first->id, 'service_document_id' => $doc->id, 'status' => 'Awaiting check']);

        $response = $this->actingAs($admin)->get(route('enquiries.show', $enquiry))->assertOk();

        // The service with a submitted document is greyed out and says why; the other can be deleted.
        $response->assertSee('enquiry-delete-service is-disabled', false)
            ->assertSee('Documents have already been submitted for this service, so it can no longer be deleted.')
            ->assertSee('aria-label="Delete '.$second->number.'"', false)
            ->assertDontSee('aria-label="Delete '.$first->number.'"', false)
            ->assertSee('js-confirm-delete', false);
    }

    public function test_an_employee_does_not_see_delete_icons_on_the_enquiry_page(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $first->update(['assigned_to' => $employee->id]);

        $this->actingAs($employee)->get(route('enquiries.show', $enquiry))
            ->assertOk()
            ->assertDontSee('enquiry-delete-service', false);
    }

    public function test_a_refused_delete_from_the_enquiry_page_comes_back_to_the_enquiry_page(): void
    {
        [$admin, $enquiry, $first, $second] = $this->enquiryWithTwoServices();
        $second->update(['status' => 'Task Completed', 'completed_at' => now()]);

        $this->actingAs($admin)->from(route('enquiries.show', $enquiry))
            ->delete(route('tickets.destroy', $second))
            ->assertRedirect(route('enquiries.show', $enquiry))
            ->assertSessionHas('error');
    }
}
