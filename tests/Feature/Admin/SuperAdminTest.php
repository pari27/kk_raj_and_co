<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => UserRole::SuperAdmin, 'is_active' => true], $attributes));
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => UserRole::Admin, 'is_active' => true], $attributes));
    }

    public function test_a_super_admin_can_use_the_admin_screens_and_the_system_page(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->get(route('dashboard'))->assertOk();
        $this->actingAs($super)->get(route('admin.settings.firm-profile'))->assertOk();
        $this->actingAs($super)->get(route('admin.audit-log'))->assertOk();
        $this->actingAs($super)->get(route('super.system'))->assertOk()->assertSee('Health')->assertSee('Recent errors');
    }

    public function test_only_the_super_admin_can_open_the_system_page(): void
    {
        $this->actingAs($this->admin())->get(route('super.system'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]))->get(route('super.system'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('super.system.log'))->assertForbidden();
    }

    public function test_the_system_menu_item_is_only_shown_to_the_super_admin(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertDontSee(route('super.system'));
        $this->actingAs($this->superAdmin())->get(route('dashboard'))->assertSee(route('super.system'));
    }

    public function test_what_a_super_admin_does_is_not_written_to_the_audit_log_but_an_admins_is(): void
    {
        $payload = ['payment_upi_id' => 'firm@bank'];

        $this->actingAs($this->superAdmin())->put(route('admin.settings.payment-details.update'), $payload);
        $this->assertSame(0, AuditLog::count());

        $this->actingAs($this->admin())->put(route('admin.settings.payment-details.update'), ['payment_upi_id' => 'other@bank']);
        $this->assertGreaterThan(0, AuditLog::count());
    }

    public function test_signing_in_as_super_admin_leaves_no_audit_trail(): void
    {
        $this->superAdmin(['email' => 'support@netiapps.com', 'password' => 'Support#2026']);

        $this->post(route('login.store'), ['email' => 'support@netiapps.com', 'password' => 'Support#2026'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, AuditLog::count());
    }

    public function test_the_super_admin_looks_like_a_plain_admin_to_everyone_else(): void
    {
        $super = $this->superAdmin(['name' => 'Netiapps Support']);
        $admin = $this->admin();

        $this->actingAs($admin);
        $this->assertSame('Admin', User::find($super->id)->name);

        $this->actingAs($super);
        $this->assertSame('Netiapps Support', User::find($super->id)->name);
    }

    public function test_notifications_caused_by_the_super_admin_do_not_use_their_name(): void
    {
        $super = $this->superAdmin(['name' => 'Netiapps Support']);
        $admin = $this->admin();

        $this->actingAs($super);
        \App\Models\Enquiry::factory()->create(['created_by' => $super->id]);

        $note = \App\Models\AppNotification::where('user_id', $admin->id)->where('type', 'enquiries')->firstOrFail();
        $this->assertStringContainsString('Admin created an enquiry', $note->body);
        $this->assertStringNotContainsString('Netiapps Support', $note->body);
    }

    public function test_the_command_creates_a_super_admin_and_can_reset_its_password(): void
    {
        $this->artisan('superadmin:create', ['email' => 'Support@Netiapps.com', 'name' => 'Netiapps Support', '--password' => 'Support#2026'])
            ->assertExitCode(0);

        $user = User::where('email', 'support@netiapps.com')->firstOrFail();
        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasSetPassword());

        $this->artisan('superadmin:create', ['email' => 'support@netiapps.com', '--password' => 'Another#2026'])->assertExitCode(1);
        $this->artisan('superadmin:create', ['email' => 'support@netiapps.com', '--password' => 'Another#2026', '--reset-password' => true])->assertExitCode(0);

        $this->post(route('login.store'), ['email' => 'support@netiapps.com', 'password' => 'Another#2026'])->assertRedirect(route('dashboard'));
    }

    public function test_the_command_refuses_an_email_that_belongs_to_another_account_or_a_weak_password(): void
    {
        $this->admin(['email' => 'admin@firm.com']);

        $this->artisan('superadmin:create', ['email' => 'admin@firm.com', '--password' => 'Support#2026'])->assertExitCode(1);
        $this->artisan('superadmin:create', ['email' => 'new@netiapps.com', '--password' => 'short'])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'new@netiapps.com']);
    }

    public function test_a_resolved_error_stops_showing_and_can_be_shown_again(): void
    {
        $fingerprint = sha1("2026-10-26 10:00:00|ERROR|Something broke");
        $super = $this->superAdmin();

        // Resolving remembers the error; the log file itself is never touched.
        $this->actingAs($super)->post(route("super.system.errors.resolve"), ["fingerprint" => $fingerprint])->assertRedirect(route("super.system"));
        $this->assertDatabaseHas("dismissed_errors", ["fingerprint" => $fingerprint]);

        $this->actingAs($super)->post(route("super.system.errors.reopen"), ["fingerprint" => $fingerprint])->assertRedirect();
        $this->assertDatabaseMissing("dismissed_errors", ["fingerprint" => $fingerprint]);
    }

    public function test_only_the_super_admin_can_resolve_errors(): void
    {
        $fingerprint = sha1("x");

        $this->actingAs($this->admin())->post(route("super.system.errors.resolve"), ["fingerprint" => $fingerprint])->assertForbidden();
        $this->actingAs($this->admin())->post(route("super.system.errors.resolve-all"))->assertForbidden();
        $this->assertDatabaseCount("dismissed_errors", 0);
    }
}
