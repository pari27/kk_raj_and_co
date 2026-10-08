<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\EmployeeDesignation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_activity_from_every_service_together(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $serviceA = Service::factory()->create(['name' => 'GST Return Filing']);
        $serviceB = Service::factory()->create(['name' => 'PAN Card Creation']);

        $serviceA->activityLogs()->create(['user_id' => $admin->id, 'action' => 'Created', 'module' => 'Service', 'record_label' => $serviceA->name, 'details' => 'Service created']);
        $serviceB->activityLogs()->create(['user_id' => $admin->id, 'action' => 'Updated', 'module' => 'Service', 'record_label' => $serviceB->name, 'details' => 'Price set to ₹500 (GST excluded)']);

        $response = $this->actingAs($admin)->get(route('admin.audit-log'));

        $response->assertOk();
        $response->assertSee('GST Return Filing');
        $response->assertSee('PAN Card Creation');
        $response->assertSee('Service created');
        $response->assertSee('Price set to ₹500 (GST excluded)');
    }

    public function test_newest_activity_appears_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();

        $service->activityLogs()->create(['user_id' => $admin->id, 'action' => 'Updated', 'module' => 'Service', 'record_label' => $service->name, 'details' => 'Older entry', 'created_at' => now()->subDay()]);
        $service->activityLogs()->create(['user_id' => $admin->id, 'action' => 'Updated', 'module' => 'Service', 'record_label' => $service->name, 'details' => 'Newer entry', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.audit-log'));

        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'Newer entry') < strpos($content, 'Older entry'));
    }

    public function test_employee_cannot_view_the_audit_log(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($employee)->get(route('admin.audit-log'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.audit-log'))->assertRedirect(route('login'));
    }

    public function test_creating_a_designation_writes_an_audit_log_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.designations.store'), [
            'name' => 'Audit Manager',
            'is_active' => '1',
        ]);

        $log = AuditLog::query()->where('module', 'Designation')->first();

        $this->assertNotNull($log);
        $this->assertSame('Created', $log->action);
        $this->assertSame('Audit Manager', $log->record_label);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_toggling_a_designation_writes_an_audit_log_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.designations.toggle-active', $designation));

        $log = AuditLog::query()->where('module', 'Designation')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Deactivated', $log->action);
    }

    public function test_login_writes_an_audit_log_entry(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'password' => bcrypt('secret123')]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $log = AuditLog::query()->where('action', 'Logged in')->first();

        $this->assertNotNull($log);
        $this->assertSame('Login', $log->module);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_failed_login_writes_an_audit_log_entry(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'password' => bcrypt('secret123')]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $log = AuditLog::query()->where('action', 'Failed login')->first();

        $this->assertNotNull($log);
        $this->assertSame('Login', $log->module);
    }

    public function test_logout_writes_an_audit_log_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('logout'));

        $log = AuditLog::query()->where('action', 'Logged out')->first();

        $this->assertNotNull($log);
        $this->assertSame('Login', $log->module);
        $this->assertSame($admin->id, $log->user_id);
    }
}
