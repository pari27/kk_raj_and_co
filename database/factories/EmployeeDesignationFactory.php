<?php

namespace Database\Factories;

use App\Models\EmployeeDesignation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDesignation>
 */
class EmployeeDesignationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'is_active' => true,
        ];
    }
}
