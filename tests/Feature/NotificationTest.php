<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Enquiry;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_are_notified_of_a_new_enquiry_but_not_the_creator(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $other = User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true]);

        $this->actingAs($creator);
        Enquiry::factory()->create(['created_by' => $creator->id]);

        $this->assertSame(1, AppNotification::where('user_id', $other->id)->where('type', 'enquiries')->count());
        $this->assertSame(0, AppNotification::where('user_id', $creator->id)->count());
    }

    public function test_user_can_view_open_and_mark_notifications_read(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $n = AppNotification::create(['user_id' => $user->id, 'type' => 'payments', 'title' => 'Payment received', 'body' => '**₹10** by UPI', 'url' => '/dashboard']);

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Payment received');
        $this->actingAs($user)->get(route('notifications.open', $n))->assertRedirect('/dashboard');
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_other_users_notifications_are_hidden_and_preferences_save(): void
    {
        $a = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $b = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $n = AppNotification::create(['user_id' => $b->id, 'type' => 'payments', 'title' => 'x', 'body' => 'y']);

        $this->actingAs($a)->get(route('notifications.open', $n))->assertNotFound();

        $this->actingAs($a)->put(route('notifications.preferences'), ['enquiries' => '0', 'documents' => '1']);
        $this->assertFalse(NotificationPreference::forUser($a)->enquiries);
    }

    public function test_admins_and_the_assignee_are_notified_when_a_service_is_assigned(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $otherAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'name' => 'Priya Das']);

        $this->actingAs($actor);
        $ticket = \App\Models\Ticket::factory()->create(['assigned_to' => $employee->id]);

        $forEmployee = AppNotification::where('user_id', $employee->id)->where('type', 'tickets')->firstOrFail();
        $this->assertSame('Service assigned to you', $forEmployee->title);

        $forAdmin = AppNotification::where('user_id', $otherAdmin->id)->where('type', 'tickets')->firstOrFail();
        $this->assertSame('Service assigned', $forAdmin->title);
        $this->assertStringContainsString('to Priya Das', $forAdmin->body);

        $this->assertSame(0, AppNotification::where('user_id', $actor->id)->count());
        $this->assertSame($ticket->number, $forAdmin->link_text);
    }

    public function test_admins_are_notified_when_a_service_is_reassigned(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $otherAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true]);
        $first = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $second = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true, 'name' => 'Tinni Sengupta']);
        $ticket = \App\Models\Ticket::factory()->create(['assigned_to' => $first->id]);
        AppNotification::query()->delete();

        $this->actingAs($actor);
        $ticket->update(['assigned_to' => $second->id]);

        $this->assertSame(1, AppNotification::where('user_id', $second->id)->where('title', 'Service assigned to you')->count());
        $this->assertStringContainsString('to Tinni Sengupta', AppNotification::where('user_id', $otherAdmin->id)->firstOrFail()->body);
    }

    public function test_an_unassigned_service_notifies_nobody(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true]);

        $this->actingAs($actor);
        \App\Models\Ticket::factory()->create(['assigned_to' => null]);

        $this->assertSame(0, AppNotification::where('type', 'tickets')->count());
    }

    private function admin(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role === 'employee' ? UserRole::Employee : UserRole::Admin,
            'is_active' => true,
        ]);
    }

    public function test_admins_are_notified_when_a_document_is_verified(): void
    {
        $actor = $this->admin('employee');
        $otherAdmin = $this->admin();
        $ticket = \App\Models\Ticket::factory()->create();
        $document = \App\Models\TicketDocument::factory()->create(['ticket_id' => $ticket->id, 'status' => 'Awaiting check']);

        $this->actingAs($actor);
        $document->update(['status' => 'Verified']);

        $notification = AppNotification::where('user_id', $otherAdmin->id)->where('title', 'Document verified')->firstOrFail();
        $this->assertSame('documents', $notification->type);
        $this->assertSame(0, AppNotification::where('user_id', $actor->id)->count());
    }

    public function test_admins_are_notified_when_an_employee_deletes_a_payment(): void
    {
        $employee = $this->admin('employee');
        $adminUser = $this->admin();
        $payment = \App\Models\Payment::factory()->create(['amount' => 2950, 'mode' => 'UPI']);

        $this->actingAs($employee);
        $payment->delete();

        $notification = AppNotification::where('user_id', $adminUser->id)->where('title', 'Payment deleted')->firstOrFail();
        $this->assertStringContainsString('₹2,950.00', $notification->body);
        $this->assertStringContainsString($employee->name, $notification->body);
    }

    public function test_no_notification_when_an_admin_deletes_a_payment(): void
    {
        $actor = $this->admin();
        $this->admin();
        $payment = \App\Models\Payment::factory()->create();

        $this->actingAs($actor);
        $payment->delete();

        $this->assertSame(0, AppNotification::where('title', 'Payment deleted')->count());
    }

    public function test_status_changes_are_notified_whether_manual_or_automatic(): void
    {
        $actor = $this->admin('employee');
        $adminUser = $this->admin();
        $ticket = \App\Models\Ticket::factory()->create();

        $this->actingAs($actor);
        $ticket->update(['status' => 'Documents Received']);

        $notification = AppNotification::where('user_id', $adminUser->id)->where('title', 'Status changed')->firstOrFail();
        $this->assertStringContainsString('automatically after document updates', $notification->body);
    }

    public function test_admins_are_told_who_created_a_client_when_an_employee_adds_one(): void
    {
        $employee = $this->admin('employee');
        $adminUser = $this->admin();

        $this->actingAs($employee);
        $customer = \App\Models\Customer::factory()->create(['name' => 'Meera Traders', 'phone' => '9876543210']);

        $notification = AppNotification::where('user_id', $adminUser->id)->where('type', 'clients')->firstOrFail();
        $this->assertSame('New client added', $notification->title);
        $this->assertStringContainsString('Meera Traders', $notification->body);
        $this->assertStringContainsString($employee->name, $notification->body);
        $this->assertStringContainsString($employee->email, $notification->body);
        $this->assertSame(route('customers.show', $customer), $notification->url);
    }

    public function test_no_notification_when_an_admin_creates_a_client(): void
    {
        $actor = $this->admin();
        $this->admin();

        $this->actingAs($actor);
        \App\Models\Customer::factory()->create();

        $this->assertSame(0, AppNotification::where('type', 'clients')->count());
    }

    public function test_reminders_are_sent_for_old_open_services_and_unpaid_fees(): void
    {
        $adminUser = $this->admin();
        $employee = $this->admin('employee');

        $old = \App\Models\Ticket::factory()->create(['assigned_to' => $employee->id, 'created_at' => now()->subDays(15)]);
        \App\Models\Ticket::factory()->create(['created_at' => now()->subDays(10)]);

        $enquiry = \App\Models\Enquiry::factory()->create(['total' => 5000]);
        \App\Models\Ticket::factory()->create([
            'enquiry_id' => $enquiry->id,
            'status' => 'Task Completed',
            'completed_at' => now()->subDays(7),
            'created_at' => now()->subDays(8),
        ]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $this->assertSame(1, AppNotification::where('user_id', $adminUser->id)->where('title', 'Service open for 15 days')->count());
        $this->assertSame(1, AppNotification::where('user_id', $employee->id)->where('title', 'Service open for 15 days')->count());
        $this->assertSame(1, AppNotification::where('user_id', $adminUser->id)->where('title', 'Fee pending for 7 days')->count());

        // Running again the same day does not duplicate.
        $this->artisan('notifications:send-reminders')->assertSuccessful();
        $this->assertSame(1, AppNotification::where('user_id', $adminUser->id)->where('title', 'Service open for 15 days')->count());
        $this->assertSame($old->number, AppNotification::where('title', 'Service open for 15 days')->value('link_text'));
    }

    public function test_reassigning_a_service_removes_the_previous_employees_notifications_about_it(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $first = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);
        $second = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($actor);
        $ticket = \App\Models\Ticket::factory()->create(['assigned_to' => $first->id]);
        $other = \App\Models\Ticket::factory()->create(['assigned_to' => $first->id]);
        $this->assertSame(2, AppNotification::where('user_id', $first->id)->count());

        $ticket->update(['assigned_to' => $second->id]);

        $this->assertSame(0, AppNotification::where('user_id', $first->id)->where('link_text', $ticket->number)->count());
        $this->assertSame(1, AppNotification::where('user_id', $first->id)->where('link_text', $other->number)->count());
        $this->assertSame(1, AppNotification::where('user_id', $second->id)->where('link_text', $ticket->number)->count());
    }
}
