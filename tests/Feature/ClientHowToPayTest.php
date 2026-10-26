<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ClientHowToPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_service_page_with_money_to_pay_has_the_how_to_pay_window(): void
    {
        Setting::current()->update(['payment_upi_id' => 'kkraj@hdfcbank']);
        $client = Customer::factory()->create(['name' => 'Manoj Kumar', 'phone' => '9876987587', 'is_active' => true]);
        $enquiry = Enquiry::factory()->create(['customer_id' => $client->id, 'total' => 5000]);
        $ticket = Ticket::factory()->create(['customer_id' => $client->id, 'enquiry_id' => $enquiry->id, 'total' => 5000]);

        Auth::guard('customer')->setUser($client);

        $this->get(route('customer.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('id="howToPayModal"', false)
            ->assertSee('data-bs-target="#howToPayModal"', false)
            ->assertSee('MANOJ-87587');
    }
}
