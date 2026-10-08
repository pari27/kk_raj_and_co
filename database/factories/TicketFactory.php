<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'TKT-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'enquiry_id' => Enquiry::factory(),
            'service_id' => Service::factory(),
            'customer_id' => Customer::factory(),
            'assigned_to' => null,
            'price' => 1000,
            'gst_percent' => 18,
            'gst_amount' => 180,
            'total' => 1180,
            'status' => 'Documents Pending',
        ];
    }
}
