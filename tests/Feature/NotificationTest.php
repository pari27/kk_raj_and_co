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
}
