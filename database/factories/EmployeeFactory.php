<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDesignation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobile' => fake()->unique()->numerify('9#########'),
            'gender' => fake()->randomElement(['Male', 'Female', 'Other']),
            'designation_id' => EmployeeDesignation::factory(),
            'photo_path' => null,
        ];
    }
}
