<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\FormalNotificationMail;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\ServiceDocument;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventEmailTest extends TestCase
{
    use RefreshDatabase;

    private function sentTo(string $address): array
    {
        return Mail::sent(FormalNotificationMail::class, fn ($mail) => $mail->hasTo($address))->values()->all();
    }

    public function test_a_new_enquiry_sends_a_formal_email_to_the_other_admins_but_not_the_creator(): void
    {
        Mail::fake();
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'email' => 'creator@example.com']);
        $other = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'name' => 'Meera Admin', 'email' => 'other@example.com']);

        $this->actingAs($creator);
        Enquiry::factory()->create(['created_by' => $creator->id]);

        $mails = $this->sentTo('other@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('Dear Meera Admin,', $mails[0]->messageBody);
        $this->assertStringContainsString('Yours sincerely,', $mails[0]->messageBody);
        $this->assertCount(0, $this->sentTo('creator@example.com'));
    }

    public function test_the_assigned_employee_gets_a_formal_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'name' => 'Priya Das', 'email' => 'priya@example.com']);

        $this->actingAs($admin);
        $ticket = Ticket::factory()->create(['assigned_to' => $employee->id]);

        $mails = $this->sentTo('priya@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('Dear Priya Das,', $mails[0]->messageBody);
        $this->assertStringContainsString($ticket->number, $mails[0]->messageBody);
    }

    public function test_a_recorded_payment_emails_the_client(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $customer = Customer::factory()->create(['name' => 'Meera Traders', 'email' => 'meera@example.com', 'email_notifications_enabled' => true]);
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($admin);
        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 500]);

        $mails = $this->sentTo('meera@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('Dear Meera Traders,', $mails[0]->messageBody);
        $this->assertStringContainsString('We acknowledge with thanks', $mails[0]->messageBody);
    }

    public function test_a_client_who_opted_out_gets_no_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $customer = Customer::factory()->create(['email' => 'quiet@example.com', 'email_notifications_enabled' => false]);
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($admin);
        Payment::factory()->create(['enquiry_id' => $enquiry->id]);

        $this->assertCount(0, $this->sentTo('quiet@example.com'));
    }

    public function test_nothing_is_sent_when_notifications_are_switched_off(): void
    {
        Mail::fake();
        Setting::current()->update(['notifications_enabled' => false]);
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'email' => 'other@example.com']);

        $this->actingAs($creator);
        Enquiry::factory()->create(['created_by' => $creator->id]);

        Mail::assertNothingSent();
    }

    public function test_test_mode_sends_everything_to_the_test_recipient(): void
    {
        Mail::fake();
        config(['mail.test_recipient' => 'tester@example.com']);
        Setting::current()->update(['test_mode' => true]);
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'email' => 'real@example.com']);

        $this->actingAs($creator);
        Enquiry::factory()->create(['created_by' => $creator->id]);

        $this->assertCount(1, $this->sentTo('tester@example.com'));
        $this->assertCount(0, $this->sentTo('real@example.com'));
    }

    public function test_a_rejected_document_always_emails_the_client_even_if_email_updates_are_off(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $customer = Customer::factory()->create(['name' => 'Manoj', 'email' => 'manoj@example.com', 'email_notifications_enabled' => false]);
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $serviceDocument = ServiceDocument::factory()->create(['service_id' => $ticket->service_id]);
        $document = TicketDocument::factory()->create(['ticket_id' => $ticket->id, 'service_document_id' => $serviceDocument->id, 'status' => 'Awaiting check']);

        $this->actingAs($admin);
        $document->update(['status' => 'Rejected', 'rejection_reason' => 'The photo is blurred.']);

        $mails = $this->sentTo('manoj@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('Dear Manoj,', $mails[0]->messageBody);
        $this->assertStringContainsString('The photo is blurred.', $mails[0]->messageBody);
    }

    public function test_client_emails_are_also_copied_to_the_copy_address_but_staff_emails_are_not(): void
    {
        Mail::fake();
        config(['mail.copy_to' => 'copy@example.com']);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $customer = Customer::factory()->create(['email' => 'client@example.com', 'email_notifications_enabled' => true]);
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($admin);
        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 500]);

        $toClient = Mail::sent(FormalNotificationMail::class, fn ($mail) => $mail->hasTo('client@example.com'));
        $this->assertCount(1, $toClient);
        $this->assertTrue($toClient->first()->hasBcc('copy@example.com'));

        $toStaff = Mail::sent(FormalNotificationMail::class, fn ($mail) => ! $mail->hasTo('client@example.com'));
        foreach ($toStaff as $mail) {
            $this->assertFalse($mail->hasBcc('copy@example.com'));
        }
    }
}
