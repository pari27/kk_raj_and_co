<?php

namespace Database\Factories;

use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketDocument>
 */
class TicketDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'service_document_id' => ServiceDocument::factory(),
            'version' => 1,
            'file_path' => 'ticket-documents/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'status' => 'Awaiting check',
            'uploaded_by' => User::factory(),
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ];
    }
}
