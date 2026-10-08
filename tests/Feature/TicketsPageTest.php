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

class TicketsPageTest extends TestCase
{
    use RefreshDatabase;

    private function createTicketFor(?User $assignee = null): Ticket
    {
        $customer = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);
        $service = Service::factory()->create();

        return Ticket::factory()->create([
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'assigned_to' => $assignee?->id,
        ]);
    }

    public function test_admin_sees_all_tickets_and_the_assigned_to_column(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $rahul = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Rahul Sen']);
        $anita = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Anita Roy']);

        $this->createTicketFor($rahul);
        $this->createTicketFor($anita);

        $response = $this->actingAs($admin)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('Tickets');
        $response->assertSee('Assigned to');
        // Tickets belonging to employees other than any single employee are visible to Admin.
        $response->assertSee('Rahul Sen');
        $response->assertSee('Anita Roy');
    }

    public function test_employee_only_sees_their_own_tickets_and_no_assigned_to_column(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Priya Das']);
        $rahul = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Rahul Sen']);
        $anita = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Anita Roy']);

        $this->createTicketFor($employee);
        $this->createTicketFor($rahul);
        $this->createTicketFor($anita);

        $response = $this->actingAs($employee)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('My tickets');
        $response->assertDontSee('Assigned to');
        $response->assertDontSee('Rahul Sen');
        $response->assertDontSee('Anita Roy');
    }

    public function test_employee_with_no_matching_tickets_sees_an_empty_but_working_page(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Someone Else']);

        $response = $this->actingAs($employee)->get(route('tickets.index'));

        $response->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }
}
