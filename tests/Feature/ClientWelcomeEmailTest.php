<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\FormalNotificationMail;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientWelcomeEmailTest extends TestCase
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

    public function test_a_new_client_gets_a_formal_welcome_email_with_a_link_to_the_portal(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('customers.store'), [
            'name' => 'Meera Traders',
            'phone' => '9876543210',
            'email' => 'meera@example.com',
            'email_notifications_enabled' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $mails = $this->sentTo('meera@example.com');
        $this->assertCount(1, $mails);
        $this->assertStringContainsString('Dear Meera Traders,', $mails[0]->messageBody);
        $this->assertStringContainsString('client portal', $mails[0]->messageBody);
        $this->assertSame(route('customer.login'), $mails[0]->actionUrl);
    }

    public function test_no_welcome_email_when_the_client_has_no_email_or_notifications_are_off(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('customers.store'), ['name' => 'No Email', 'phone' => '9876543211', 'is_active' => '1']);
        $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Opted Out', 'phone' => '9876543212', 'email' => 'quiet@example.com', 'is_active' => '1',
        ]);

        Mail::assertNothingSent();
        $this->assertSame(2, Customer::count());
    }
}
