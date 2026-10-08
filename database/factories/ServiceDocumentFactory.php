<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceDocument>
 */
class ServiceDocumentFactory extends Factory
{
    protected $model = ServiceDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->unique()->words(2, true),
            'instructions' => fake()->sentence(),
            'allowed_formats' => 'PDF, JPG, PNG',
            'max_file_size_kb' => 5120,
            'is_mandatory' => true,
        ];
    }
}
