<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function createTicketWithDocument(array $documentAttributes = [], array $ticketAttributes = []): array
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);

        $serviceDocument = ServiceDocument::factory()->create([
            ...$documentAttributes,
            'service_id' => $service->id,
        ]);

        $ticket = Ticket::factory()->create([
            ...$ticketAttributes,
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        return [$ticket, $serviceDocument];
    }

    public function test_admin_can_upload_a_document_for_a_ticket(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $response = $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $document = TicketDocument::query()->where('ticket_id', $ticket->id)->firstOrFail();
        $this->assertSame(1, $document->version);
        $this->assertSame('Awaiting check', $document->status);
        $this->assertSame($admin->id, $document->uploaded_by);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_reuploading_increments_the_version(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf'),
        ]);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(2, TicketDocument::query()->where('ticket_id', $ticket->id)->count());
        $this->assertSame(2, TicketDocument::query()->where('ticket_id', $ticket->id)->max('version'));
    }

    public function test_upload_rejects_a_disallowed_file_format(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument(['allowed_formats' => 'PDF']);

        $response = $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('photo.png', 100, 'image/png'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, TicketDocument::query()->count());
    }

    public function test_upload_rejects_a_file_exceeding_the_max_size(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument(['max_file_size_kb' => 100]);

        $response = $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('big.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, TicketDocument::query()->count());
    }

    public function test_unassigned_employee_cannot_upload_a_document(): void
    {
        Storage::fake('public');
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $this->actingAs($employee)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_assigned_employee_can_verify_an_uploaded_document(): void
    {
        Storage::fake('public');
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument([], ['assigned_to' => $employee->id]);

        $document = TicketDocument::factory()->create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
        ]);

        $response = $this->actingAs($employee)->patch(route('tickets.documents.verify', [$ticket, $document]));

        $response->assertRedirect(route('tickets.show', $ticket));
        $document->refresh();
        $this->assertSame('Verified', $document->status);
        $this->assertSame($employee->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);
    }

    public function test_rejecting_a_document_requires_a_reason(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $document = TicketDocument::factory()->create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
        ]);

        $response = $this->actingAs($admin)->patch(route('tickets.documents.reject', [$ticket, $document]), [
            'rejection_reason' => 'Image is blurry, please re-upload.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $document->refresh();
        $this->assertSame('Rejected', $document->status);
        $this->assertSame('Image is blurry, please re-upload.', $document->rejection_reason);
    }

    public function test_unassigned_employee_cannot_verify_or_reject(): void
    {
        Storage::fake('public');
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $document = TicketDocument::factory()->create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
        ]);

        $this->actingAs($employee)->patch(route('tickets.documents.verify', [$ticket, $document]))->assertForbidden();
        $this->actingAs($employee)->patch(route('tickets.documents.reject', [$ticket, $document]), [
            'rejection_reason' => 'Not clear.',
        ])->assertForbidden();
    }

    public function test_uploading_the_first_document_moves_ticket_to_documents_received(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument();

        $this->assertSame(TicketStatus::DocumentsPending, $ticket->status);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(TicketStatus::DocumentsReceived, $ticket->refresh()->status);
    }

    public function test_uploading_one_of_two_mandatory_documents_moves_ticket_to_partially_received(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $firstDoc] = $this->createTicketWithDocument(['is_mandatory' => true]);
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'is_mandatory' => true]);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $firstDoc->id,
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(TicketStatus::PartiallyReceived, $ticket->refresh()->status);
    }

    public function test_uploading_the_remaining_mandatory_document_moves_ticket_to_documents_received(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $firstDoc] = $this->createTicketWithDocument(['is_mandatory' => true]);
        $secondDoc = ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'is_mandatory' => true]);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $firstDoc->id,
            'file' => UploadedFile::fake()->create('doc1.pdf', 100, 'application/pdf'),
        ]);
        $this->assertSame(TicketStatus::PartiallyReceived, $ticket->refresh()->status);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $secondDoc->id,
            'file' => UploadedFile::fake()->create('doc2.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(TicketStatus::DocumentsReceived, $ticket->refresh()->status);
    }

    public function test_verifying_all_mandatory_documents_moves_ticket_to_under_verification(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $mandatoryDoc] = $this->createTicketWithDocument(['is_mandatory' => true]);
        $optionalDoc = ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'is_mandatory' => false]);

        $mandatory = TicketDocument::factory()->create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $mandatoryDoc->id,
            'status' => 'Awaiting check',
        ]);

        $this->actingAs($admin)->patch(route('tickets.documents.verify', [$ticket, $mandatory]));

        $this->assertSame(TicketStatus::UnderVerification, $ticket->refresh()->status);
    }

    public function test_rejecting_a_document_moves_ticket_to_additional_documents_required(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument([], ['status' => 'Documents Received']);

        $document = TicketDocument::factory()->create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
            'status' => 'Awaiting check',
        ]);

        $this->actingAs($admin)->patch(route('tickets.documents.reject', [$ticket, $document]), [
            'rejection_reason' => 'Blurry scan.',
        ]);

        $this->assertSame(TicketStatus::AdditionalDocumentsRequired, $ticket->refresh()->status);
    }

    public function test_document_upload_does_not_change_a_manually_advanced_ticket(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$ticket, $serviceDocument] = $this->createTicketWithDocument([], ['status' => 'On Hold']);

        $this->actingAs($admin)->post(route('tickets.documents.store', $ticket), [
            'service_document_id' => $serviceDocument->id,
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(TicketStatus::OnHold, $ticket->refresh()->status);
    }
}
