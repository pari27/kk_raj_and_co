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
            'comment' => 'Test note',
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
            ->patch(route('tickets.update-status', $ticket), ['status' => 'On Hold', 'comment' => 'Test note'])
            ->assertForbidden();
    }

    public function test_only_the_three_manual_statuses_are_accepted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket();

        $response = $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'Documents Pending',
            'comment' => 'Test note',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_the_comment_typed_with_a_status_is_saved_shown_on_the_services_list_and_kept_in_the_timeline(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'Documents Pending']);

        $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'On Hold',
            'comment' => 'Waiting for the client to send the bank statement',
        ])->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame('Waiting for the client to send the bank statement', $ticket->status_comment);
        $this->assertSame('On Hold', $ticket->status_comment_for);

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('On Hold')
            ->assertSee('Waiting for the client to send the bank statement');

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertSee('On Hold — Waiting for the client to send the bank statement');
    }

    public function test_a_comment_is_not_shown_once_the_status_has_moved_on(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'On Hold']);
        $ticket->update(['status_comment' => 'Paused for the audit', 'status_comment_for' => 'On Hold']);

        $this->actingAs($admin)->get(route('tickets.index'))->assertSee('Paused for the audit');

        // The system moves the service on by itself (for example when documents arrive).
        $ticket->update(['status' => 'Work In Progress']);

        $this->actingAs($admin)->get(route('tickets.index'))->assertDontSee('Paused for the audit');
        $this->assertNull($ticket->fresh()->currentStatusComment());
    }
    public function test_the_services_list_carries_each_status_comment_for_the_search_box(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'On Hold']);
        $ticket->update(['status_comment' => 'Waiting for the bank statement', 'status_comment_for' => 'On Hold']);

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertSee('data-comment="Waiting for the bank statement"', false)
            ->assertSee("row.getAttribute('data-comment')", false);

        // A comment that no longer belongs to the current status is not searchable either.
        $ticket->update(['status' => 'Work In Progress']);

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertDontSee('data-comment="Waiting for the bank statement"', false);
    }
    public function test_the_status_comment_can_be_at_most_50_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'Documents Pending']);

        $this->actingAs($admin)
            ->patch(route('tickets.update-status', $ticket), ['status' => 'On Hold', 'comment' => str_repeat('a', 51)])
            ->assertSessionHasErrors('comment');
        $this->assertSame('Documents Pending', $ticket->fresh()->status->value);

        $this->actingAs($admin)
            ->patch(route('tickets.update-status', $ticket), ['status' => 'On Hold', 'comment' => str_repeat('a', 50)])
            ->assertSessionHasNoErrors();
        $this->assertSame(str_repeat('a', 50), $ticket->fresh()->status_comment);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))->assertSee('maxlength="50"', false);
    }
    public function test_open_services_are_listed_newest_first_by_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $first = $this->createTicket(['status' => 'Documents Pending']);
        $second = $this->createTicket(['status' => 'Documents Pending']);
        $second->forceFill(['created_at' => $first->created_at])->save(); // created in the same moment

        $response = $this->actingAs($admin)->get(route('tickets.index'));

        // The newer service comes first, even when both were created in the same second.
        $response->assertSeeInOrder([$second->number, $first->number]);

        // And the sort box says so, instead of "Oldest first".
        $response->assertSee('<option value="asc" selected>Newest first</option>', false);
        $response->assertSee('<option value="desc">Oldest first</option>', false);
    }
    public function test_the_status_comment_has_its_own_column_after_the_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'On Hold']);
        $ticket->update(['status_comment' => 'Paused for the audit', 'status_comment_for' => 'On Hold']);

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertSeeInOrder(['>Status', '<th>Comment</th>', '<th>Documents</th>'], false)
            ->assertSeeInOrder(['>On Hold<', 'class="ticket-status-comment"', 'Paused for the audit'], false);
    }
    public function test_a_completed_service_cannot_be_reassigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $ticket = $this->createTicket(['status' => 'Task Completed', 'assigned_to' => null]);

        // The page shows the choice greyed out, with a note.
        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertSee('<fieldset disabled>', false)
            ->assertSee("After task completion, the service can't be reassigned.", false);

        // And the server refuses it even if the form is forced.
        $this->actingAs($admin)
            ->patch(route('tickets.reassign', $ticket), ['assigned_to' => $employee->id])
            ->assertStatus(422);
        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_an_open_service_can_still_be_reassigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $ticket = $this->createTicket(['status' => 'Work In Progress']);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertDontSee("After task completion, the service can't be reassigned.", false);

        $this->actingAs($admin)
            ->patch(route('tickets.reassign', $ticket), ['assigned_to' => $employee->id])
            ->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame($employee->id, $ticket->fresh()->assigned_to);
    }
    public function test_the_same_four_cards_stay_on_both_the_open_and_completed_tabs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->createTicket(['status' => 'Documents Pending']);

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertSeeInOrder(['Pending services', 'Waiting for documents', 'Completed (this month)', 'Pending payment'])
            ->assertSee('id="pendingCards"', false)
            // There is no second set of cards for the Completed tab any more.
            ->assertDontSee('id="historyCards"', false)
            ->assertDontSee('Fees collected')
            ->assertDontSee('Average time');
    }
    public function test_the_staff_progress_bar_has_a_documents_received_step_and_no_paid_step(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $open = $this->createTicket(['status' => 'On Hold']);
        $this->actingAs($admin)->get(route('tickets.show', $open))
            ->assertSeeInOrder(['Documents', 'Documents received', 'Verification', 'Work in progress', 'Submitted', 'Completed'])
            ->assertSee('Step 4 of 6')
            ->assertDontSee('>Paid<', false);

        $done = $this->createTicket(['status' => 'Task Completed', 'completed_at' => now()]);
        $this->actingAs($admin)->get(route('tickets.show', $done))
            ->assertSee('Step 6 of 6')
            ->assertDontSee('Step 7 of');
    }

    public function test_documents_received_shows_how_many_documents_are_in_while_only_some_are(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'Partially Received']);
        $docs = \App\Models\ServiceDocument::factory()->count(3)->create(['service_id' => $ticket->service_id, 'is_mandatory' => true]);
        \App\Models\TicketDocument::factory()->create(['ticket_id' => $ticket->id, 'service_document_id' => $docs[0]->id, 'status' => 'Awaiting check']);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertSee('Step 2 of 6')
            ->assertSeeInOrder(['Documents received', '1 of 3 received'])
            ->assertDontSee('>Now<', false);

        // Once everything is in, the step is ticked and the count gives way to the next step.
        $ticket->update(['status' => 'Documents Received']);
        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertSee('Step 3 of 6')
            ->assertDontSee('1 of 3 received');
    }
    public function test_the_person_signed_in_sees_themselves_as_me_in_the_assign_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'Paramita Sengupta', 'is_active' => true]);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'Rina Roy', 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Priya Das', 'is_active' => true]);
        $ticket = $this->createTicket(['status' => 'Work In Progress']);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertSee('Paramita Sengupta (me)')
            ->assertDontSee('Admin — Paramita Sengupta')
            ->assertSee('Admin — Rina Roy')
            ->assertSee('Priya Das');

        $this->actingAs($admin)->get(route('enquiries.create'))
            ->assertSee("return assignee.name + ' (me)';", false);
    }
    public function test_a_comment_is_required_when_changing_the_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket();

        $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), ['status' => 'On Hold'])
            ->assertSessionHasErrors('comment');
    }

    public function test_status_cannot_be_changed_after_task_completion(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = $this->createTicket(['status' => 'Task Completed', 'completed_at' => now()]);

        $response = $this->actingAs($admin)->patch(route('tickets.update-status', $ticket), [
            'status' => 'On Hold',
            'comment' => 'Test note',
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
            'comment' => 'Test note',
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
