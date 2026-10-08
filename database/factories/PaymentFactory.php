<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enquiry_id' => Enquiry::factory(),
            'amount' => fake()->randomFloat(2, 500, 10000),
            'mode' => fake()->randomElement(['UPI', 'Bank transfer', 'Cash', 'Cheque']),
            'reference' => fake()->bothify('REF-########'),
            'received_by' => User::factory(),
            'paid_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'is_partial' => false,
        ];
    }
}
