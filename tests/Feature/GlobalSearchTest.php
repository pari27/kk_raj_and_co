<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_clients_by_name_and_phone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Customer::factory()->create(['name' => 'Meera Traders', 'phone' => '9876543210']);
        Customer::factory()->create(['name' => 'Someone Else', 'phone' => '9000000000']);

        $this->actingAs($admin)->get(route('search', ['q' => 'Meera']))
            ->assertOk()->assertSee('Meera Traders')->assertDontSee('Someone Else');

        $this->actingAs($admin)->get(route('search', ['q' => '98765']))
            ->assertOk()->assertSee('Meera Traders');
    }

    public function test_a_short_search_asks_for_more_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('search', ['q' => 'a']))
            ->assertOk()->assertSee('at least 2 characters');
    }

    public function test_an_employee_only_finds_services_assigned_to_them(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $customer = Customer::factory()->create(['name' => 'Zed Client']);

        $mine = Ticket::factory()->create(['customer_id' => $customer->id, 'assigned_to' => $employee->id, 'number' => 'SRV-2026-7771']);
        $theirs = Ticket::factory()->create(['customer_id' => $customer->id, 'assigned_to' => $other->id, 'number' => 'SRV-2026-7772']);

        $this->actingAs($employee)->get(route('search', ['q' => 'SRV-2026-777']))
            ->assertOk()->assertSee('SRV-2026-7771')->assertDontSee('SRV-2026-7772');
    }

    public function test_guests_cannot_search(): void
    {
        $this->get(route('search', ['q' => 'abc']))->assertRedirect(route('login'));
    }
}
