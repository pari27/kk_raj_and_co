<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\EnquiryStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payment is validated against the enquiry's total, not the ticket's own
     * total, so tests need a real enquiry total to record a payment against.
     */
    private function createTicket(float $total, array $ticketAttributes = []): Ticket
    {
        $enquiry = Enquiry::factory()->create(['total' => $total]);

        return Ticket::factory()->create([
            ...$ticketAttributes,
            'enquiry_id' => $enquiry->id,
            'total' => $total,
        ]);
    }

    public function test_admin_can_view_the_payments_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('Payments');
    }

    public function test_a_note_is_saved_with_the_payment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 1000,
            'mode' => 'Cash',
            'note' => "Paid by the client's accountant",
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $payment = Payment::query()->where('enquiry_id', $ticket->enquiry_id)->firstOrFail();
        $this->assertSame("Paid by the client's accountant", $payment->note);
    }

    public function test_full_payment_marks_the_ticket_as_fully_paid_without_changing_its_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(5000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 5000,
            'mode' => 'UPI',
            'reference' => 'UPI123',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('payments.index'));
        $ticket->refresh();
        $this->assertSame(TicketStatus::TaskCompleted, $ticket->status);
        $this->assertSame(PaymentStatus::FullyPaid, $ticket->paymentStatus());

        $payment = Payment::query()->where('enquiry_id', $ticket->enquiry_id)->firstOrFail();
        $this->assertFalse($payment->is_partial);
        $this->assertSame($admin->id, $payment->received_by);
    }

    public function test_partial_payment_keeps_ticket_task_completed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(5000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 2000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $this->assertSame(TicketStatus::TaskCompleted, $ticket->refresh()->status);
        $this->assertSame(2000.0, $ticket->amountPaid());
        $this->assertSame(3000.0, $ticket->balanceDue());

        $payment = Payment::query()->where('enquiry_id', $ticket->enquiry_id)->firstOrFail();
        $this->assertTrue($payment->is_partial);
    }

    public function test_can_record_an_advance_payment_for_a_ticket_that_is_not_completed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::WorkInProgress]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 400,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('payments.index'));
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(TicketStatus::WorkInProgress, $ticket->refresh()->status);
        $this->assertSame(600.0, $ticket->balanceDue());
    }

    public function test_marking_a_fully_prepaid_ticket_completed_just_becomes_task_completed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::WorkInProgress]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 1000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $this->assertSame(TicketStatus::WorkInProgress, $ticket->refresh()->status);
        $this->assertSame(PaymentStatus::FullyPaid, $ticket->paymentStatus());

        $response = $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => TicketStatus::TaskCompleted->value,
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $ticket->refresh();
        $this->assertSame(TicketStatus::TaskCompleted, $ticket->status);
        $this->assertSame(PaymentStatus::FullyPaid, $ticket->paymentStatus());
        $this->assertSame('Closed', $ticket->enquiry->refresh()->status);
    }

    public function test_enquiry_only_closes_once_every_ticket_is_completed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $enquiry = Enquiry::factory()->create(['total' => 2000]);
        $ticketOne = Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'total' => 1000, 'status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $ticketTwo = Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'total' => 1000, 'status' => TicketStatus::WorkInProgress]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $enquiry->id,
            'amount' => 2000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $this->assertSame('Open', $enquiry->refresh()->status);

        $this->actingAs($admin)->patch(route('tickets.update-status', $ticketTwo), [
            'status' => TicketStatus::TaskCompleted->value,
        ]);

        $this->assertSame('Closed', $enquiry->refresh()->status);
    }

    public function test_editing_a_payment_down_reopens_a_closed_enquiry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create(['enquiry_id' => $ticket->enquiry_id, 'amount' => 1000, 'is_partial' => false]);
        EnquiryStatusService::recompute($ticket->enquiry);
        $this->assertSame('Closed', $ticket->enquiry->refresh()->status);

        $this->actingAs($admin)->put(route('payments.update', $payment), [
            'amount' => 600,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $this->assertSame('Open', $ticket->enquiry->refresh()->status);
    }

    public function test_cannot_record_payment_for_an_enquiry_with_no_balance_due(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        Payment::factory()->create(['enquiry_id' => $ticket->enquiry_id, 'amount' => 1000]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 1,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_cannot_record_payment_larger_than_balance_due(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 1500,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_a_payment_settles_every_ticket_sharing_its_enquiry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $enquiry = Enquiry::factory()->create(['total' => 4000]);
        $ticketOne = Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'total' => 2500, 'status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $ticketTwo = Ticket::factory()->create(['enquiry_id' => $enquiry->id, 'total' => 1500, 'status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'enquiry_id' => $enquiry->id,
            'amount' => 4000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $this->assertSame(PaymentStatus::FullyPaid, $ticketOne->refresh()->paymentStatus());
        $this->assertSame(PaymentStatus::FullyPaid, $ticketTwo->refresh()->paymentStatus());
    }

    public function test_employee_can_also_view_and_record_payments(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(500, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $this->actingAs($employee)->get(route('payments.index'))->assertOk();

        $response = $this->actingAs($employee)->post(route('payments.store'), [
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 500,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('payments.index'));
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_admin_can_edit_a_payment_amount(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(5000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create([
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 2000,
            'is_partial' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('payments.update', $payment), [
            'amount' => 5000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('payments.index'));
        $this->assertSame(5000.0, (float) $payment->refresh()->amount);
        $this->assertFalse($payment->is_partial);
        $ticket->refresh();
        $this->assertSame(TicketStatus::TaskCompleted, $ticket->status);
        $this->assertSame(PaymentStatus::FullyPaid, $ticket->paymentStatus());
    }

    public function test_editing_a_payment_down_changes_payment_status_to_partially_paid(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(5000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create([
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 5000,
            'is_partial' => false,
        ]);

        $this->actingAs($admin)->put(route('payments.update', $payment), [
            'amount' => 3000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $ticket->refresh();
        $this->assertSame(TicketStatus::TaskCompleted, $ticket->status);
        $this->assertSame(PaymentStatus::PartiallyPaid, $ticket->paymentStatus());
    }

    public function test_cannot_edit_a_payment_above_the_enquirys_balance(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create(['enquiry_id' => $ticket->enquiry_id, 'amount' => 500]);

        $response = $this->actingAs($admin)->put(route('payments.update', $payment), [
            'amount' => 1500,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(500.0, (float) $payment->refresh()->amount);
    }

    public function test_employee_can_edit_a_payment(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create(['enquiry_id' => $ticket->enquiry_id, 'amount' => 500]);

        $this->actingAs($employee)->put(route('payments.update', $payment), [
            'amount' => 1000,
            'mode' => 'Cash',
            'paid_at' => now()->format('Y-m-d'),
        ])->assertRedirect(route('payments.index'));
    }

    public function test_admin_can_delete_a_payment_without_changing_ticket_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(5000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create([
            'enquiry_id' => $ticket->enquiry_id,
            'amount' => 5000,
            'is_partial' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('payments.destroy', $payment));

        $response->assertRedirect(route('payments.index'));
        $this->assertSame(0, Payment::query()->count());
        $ticket->refresh();
        $this->assertSame(TicketStatus::TaskCompleted, $ticket->status);
        $this->assertSame(PaymentStatus::Unpaid, $ticket->paymentStatus());
    }

    public function test_employee_cannot_delete_a_payment(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(1000, ['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $payment = Payment::factory()->create(['enquiry_id' => $ticket->enquiry_id, 'amount' => 1000]);

        $this->actingAs($employee)->delete(route('payments.destroy', $payment))->assertForbidden();
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_export_returns_a_csv_download(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('payments.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
