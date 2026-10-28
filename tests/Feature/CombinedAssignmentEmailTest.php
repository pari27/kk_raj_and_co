<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\FormalNotificationMail;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CombinedAssignmentEmailTest extends TestCase
{
    use RefreshDatabase;

    private function sentTo(string $address): array
    {
        return Mail::sent(FormalNotificationMail::class, fn ($mail) => $mail->hasTo($address))->values()->all();
    }

    public function test_an_employee_given_several_services_in_one_enquiry_gets_one_email_listing_them(): void
    {
        Mail::fake();
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'email' => 'creator@firm.com']);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'name' => 'Second Admin', 'email' => 'second@firm.com']);
        $priya = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'name' => 'Priya Das', 'email' => 'priya@firm.com']);
        $client = Customer::factory()->create(['name' => 'Manoj', 'is_active' => true]);
        [$pan, $gst, $tan] = [Service::factory()->create(['name' => 'PAN Registration']), Service::factory()->create(['name' => 'GST Registration']), Service::factory()->create(['name' => 'TAN Registration'])];

        $this->actingAs($creator)->post(route('enquiries.store'), [
            'customer_id' => $client->id,
            'services' => [
                ['service_id' => $pan->id, 'assigned_to' => $priya->id],
                ['service_id' => $gst->id, 'assigned_to' => $priya->id],
                ['service_id' => $tan->id, 'assigned_to' => $priya->id],
            ],
        ])->assertRedirect();

        $mails = $this->sentTo('priya@firm.com');
        $this->assertCount(1, $mails);
        $body = $mails[0]->messageBody;
        $this->assertStringContainsString('Dear Priya Das,', $body);
        foreach (['PAN Registration', 'GST Registration', 'TAN Registration'] as $name) {
            $this->assertStringContainsString($name, $body);
        }
        $this->assertStringContainsString('(for Manoj)', $body);

        // The other admin gets ONE email: the new enquiry together with every service and who got it.
        $adminMails = $this->sentTo('second@firm.com');
        $this->assertCount(1, $adminMails);
        $this->assertStringContainsString('New enquiry recorded', $adminMails[0]->mailSubject);
        $this->assertStringContainsString('recorded a new enquiry', $adminMails[0]->messageBody);
        $this->assertStringContainsString('GST Registration (for Manoj) → Priya Das', $adminMails[0]->messageBody);
        $this->assertStringContainsString('TAN Registration (for Manoj) → Priya Das', $adminMails[0]->messageBody);
        $this->assertStringNotContainsString('{{', $adminMails[0]->messageBody);
        $this->assertCount(0, $this->sentTo('creator@firm.com'));
    }
    public function test_services_for_different_employees_give_each_one_their_own_email(): void
    {
        Mail::fake();
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $priya = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'email' => 'priya@firm.com']);
        $tinni = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'email' => 'tinni@firm.com']);
        $client = Customer::factory()->create(['is_active' => true]);
        [$a, $b] = [Service::factory()->create(), Service::factory()->create()];

        $this->actingAs($creator)->post(route('enquiries.store'), [
            'customer_id' => $client->id,
            'services' => [
                ['service_id' => $a->id, 'assigned_to' => $priya->id],
                ['service_id' => $b->id, 'assigned_to' => $tinni->id],
            ],
        ])->assertRedirect();

        $this->assertCount(1, $this->sentTo('priya@firm.com'));
        $this->assertCount(1, $this->sentTo('tinni@firm.com'));
        $this->assertStringContainsString('has been assigned to you', $this->sentTo('priya@firm.com')[0]->messageBody);
    }

    public function test_a_single_service_assigned_on_its_own_is_still_emailed_straight_away(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $priya = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'email' => 'priya@firm.com']);

        $this->actingAs($admin);
        Ticket::factory()->create(['assigned_to' => $priya->id]);

        $this->assertCount(1, $this->sentTo('priya@firm.com'));
    }
}
