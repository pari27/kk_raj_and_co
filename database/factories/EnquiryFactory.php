<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ENQ-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'notes' => null,
            'subtotal' => 0,
            'gst_total' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'Open',
        ];
    }
}
