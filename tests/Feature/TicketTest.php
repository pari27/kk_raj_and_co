<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function createTicket(array $attributes = []): Ticket
    {
        $customer = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        $service = Service::factory()->create();

        return Ticket::factory()->create([
            ...$attributes,
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_assigned_employee_can_update_their_tickets_status(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(['assigned_to' => $employee->id, 'status' => 'Documents Pending']);

        $response = $this->actingAs($employee)->patch(route('tickets.update-status', $ticket), [
            'status' => 'Submitted to Department',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame('Submitted to Department', $ticket->refresh()->status->value);
    }

    public function test_employee_cannot_update_status_of_a_ticket_assigned_to_someone_else(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(['assigned_to' => $other->id]);

        $this->actingAs($employee)
            ->patch(route('tickets.update-status', $ticket), ['status' => 'On Hold'])
            ->assertForbidden();
    }

    public function test_only_the_three_manual_statuses_are_accepted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket();

        $response = $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'Documents Pending',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_status_cannot_be_changed_after_task_completion(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'Task Completed', 'completed_at' => now()]);

        $response = $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'On Hold',
        ]);

        $response->assertStatus(422);
        $this->assertSame('Task Completed', $ticket->refresh()->status->value);
    }

    public function test_marking_task_completed_sets_completed_at(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket();

        $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'Task Completed',
        ]);

        $this->assertNotNull($ticket->refresh()->completed_at);
    }

    public function test_only_admin_can_reassign_a_ticket(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $newAssignee = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(['assigned_to' => $employee->id]);

        $this->actingAs($employee)
            ->patch(route('tickets.reassign', $ticket), ['assigned_to' => $newAssignee->id])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('tickets.reassign', $ticket), ['assigned_to' => $newAssignee->id])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame($newAssignee->id, $ticket->refresh()->assigned_to);
    }

    public function test_employee_cannot_view_a_ticket_assigned_to_someone_else(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $ticket = $this->createTicket(['assigned_to' => $other->id]);

        $this->actingAs($employee)->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }
}
