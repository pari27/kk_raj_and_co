<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\FormalNotificationMail;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnquiryClientEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    }

    private function sentTo(string $address): array
    {
        return Mail::sent(FormalNotificationMail::class, fn ($mail) => $mail->hasTo($address))->values()->all();
    }

    private function serviceWithDocuments(): Service
    {
        $service = Service::factory()->create(['name' => 'PAN Registration']);
        ServiceDocument::factory()->create(['service_id' => $service->id, 'name' => 'Aadhaar Card', 'is_mandatory' => true]);
        ServiceDocument::factory()->create(['service_id' => $service->id, 'name' => 'Birth Certificate', 'is_mandatory' => false]);

        return $service;
    }

    public function test_a_brand_new_client_gets_a_welcome_and_a_document_request_from_the_enquiry_form(): void
    {
        Mail::fake();
        $service = $this->serviceWithDocuments();

        $this->actingAs($this->admin())->post(route('enquiries.store'), [
            'customer_phone' => '9123456780',
            'customer_name' => 'Sharma Tea Traders',
            'customer_email' => 'sharma@example.com',
            'customer_email_notifications' => '1',
            'services' => [['service_id' => $service->id]],
        ])->assertRedirect();

        $mails = $this->sentTo('sharma@example.com');
        $this->assertCount(2, $mails);

        $bodies = collect($mails)->pluck('messageBody');
        $this->assertTrue($bodies->contains(fn ($body) => str_contains($body, 'We have created a client account for you')));

        $request = $bodies->first(fn ($body) => str_contains($body, 'following documents'));
        $this->assertNotNull($request);
        $this->assertStringContainsString('Dear Sharma Tea Traders,', $request);
        $this->assertStringContainsString('PAN Registration (', $request);
        $this->assertStringContainsString('- Aadhaar Card', $request);
        $this->assertStringContainsString('- Birth Certificate (optional)', $request);
    }

    public function test_an_existing_client_gets_only_the_document_request(): void
    {
        Mail::fake();
        $service = $this->serviceWithDocuments();
        $client = Customer::factory()->create(['email' => 'meera@example.com', 'email_notifications_enabled' => true, 'is_active' => true]);

        // The form sends the client's notification choice along (it is pre-filled from their record).
        $this->actingAs($this->admin())->post(route('enquiries.store'), [
            'customer_id' => $client->id,
            'customer_email_notifications' => '1',
            'services' => [['service_id' => $service->id]],
        ])->assertRedirect();

        $mails = $this->sentTo('meera@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('following documents', $mails[0]->messageBody);
        $this->assertSame(route('customer.dashboard'), $mails[0]->actionUrl);
    }

    public function test_a_client_who_does_not_want_email_gets_neither(): void
    {
        Mail::fake();
        $service = $this->serviceWithDocuments();

        $this->actingAs($this->admin())->post(route('enquiries.store'), [
            'customer_phone' => '9123456781',
            'customer_name' => 'Quiet Client',
            'customer_email' => 'quiet@example.com',
            'services' => [['service_id' => $service->id]],
        ])->assertRedirect();

        $this->assertCount(0, $this->sentTo('quiet@example.com'));
    }

    public function test_no_document_request_when_the_services_need_no_documents(): void
    {
        Mail::fake();
        $service = Service::factory()->create();
        $client = Customer::factory()->create(['email' => 'meera@example.com', 'email_notifications_enabled' => true, 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('enquiries.store'), [
            'customer_id' => $client->id,
            'services' => [['service_id' => $service->id]],
        ])->assertRedirect();

        $this->assertCount(0, $this->sentTo('meera@example.com'));
    }
}
