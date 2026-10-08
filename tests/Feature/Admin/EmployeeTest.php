<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Mail\EmployeeInvitationMail;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $profileAttrs
     * @param  array<string, mixed>  $userAttrs
     */
    private function createEmployeeUser(array $profileAttrs = [], array $userAttrs = []): User
    {
        $profile = Employee::factory()->create($profileAttrs);

        return User::factory()->create([
            ...$userAttrs,
            'role' => UserRole::Employee,
            'employee_id' => $profile->id,
        ]);
    }

    public function test_admin_can_create_an_employee(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '9876577341',
            'email' => 'neha.kapoor@firm.com',
            'designation_id' => $designation->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $employee = User::query()->where('email', 'neha.kapoor@firm.com')->firstOrFail();
        $this->assertSame(UserRole::Employee, $employee->role);
        $this->assertTrue($employee->is_active);
        $this->assertFalse($employee->hasSetPassword());

        $profile = $employee->profile;
        $this->assertNotNull($profile);
        $this->assertSame('9876577341', $profile->mobile);
        $this->assertSame($designation->id, $profile->designation_id);
        $this->assertSame('Neha Kapoor', $profile->name);

        Mail::assertSent(EmployeeInvitationMail::class, fn ($mail) => $mail->hasTo('neha.kapoor@firm.com'));
    }

    public function test_creating_an_employee_writes_an_audit_log_entry(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '9876577341',
            'email' => 'neha.kapoor@firm.com',
            'designation_id' => $designation->id,
        ]);

        $log = AuditLog::query()->where('module', 'Employee')->first();

        $this->assertNotNull($log);
        $this->assertSame('Created', $log->action);
        $this->assertSame('Neha Kapoor', $log->record_label);
    }

    public function test_employee_mobile_must_be_exactly_10_digits(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '12345',
            'email' => 'neha.kapoor@firm.com',
            'designation_id' => $designation->id,
        ]);

        $response->assertSessionHasErrors('mobile');
        $this->assertSame(0, User::query()->where('role', UserRole::Employee)->count());
    }

    public function test_employee_mobile_and_email_must_be_unique(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);
        $this->createEmployeeUser(['mobile' => '9876577341'], ['email' => 'taken@firm.com']);

        $response = $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '9876577341',
            'email' => 'taken@firm.com',
            'designation_id' => $designation->id,
        ]);

        $response->assertSessionHasErrors(['mobile', 'email']);
    }

    public function test_employee_photo_upload_is_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Neha Kapoor',
            'gender' => 'Female',
            'mobile' => '9876577341',
            'email' => 'neha.kapoor@firm.com',
            'designation_id' => $designation->id,
            'photo' => UploadedFile::fake()->image('neha.jpg'),
        ]);

        $employee = User::query()->where('email', 'neha.kapoor@firm.com')->firstOrFail();
        $this->assertNotNull($employee->profile->photo_path);
        Storage::disk('public')->assertExists($employee->profile->photo_path);
    }

    public function test_admin_can_update_an_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $designation = EmployeeDesignation::factory()->create(['is_active' => true]);
        $employee = $this->createEmployeeUser(['mobile' => '9876500000']);

        $response = $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'name' => 'Updated Name',
            'gender' => 'Male',
            'mobile' => '9876511111',
            'email' => $employee->email,
            'designation_id' => $designation->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.employees.index'));
        $employee->refresh();
        $this->assertSame('Updated Name', $employee->name);
        $this->assertSame('9876511111', $employee->profile->mobile);
        $this->assertSame($designation->id, $employee->profile->designation_id);
        $this->assertSame('Updated Name', $employee->profile->fresh()->name);
    }

    public function test_admin_can_toggle_an_employee_active(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = $this->createEmployeeUser([], ['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.employees.toggle-active', $employee));

        $this->assertFalse($employee->refresh()->is_active);
    }

    public function test_employee_cannot_manage_the_employees_master(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $other = $this->createEmployeeUser();

        $this->actingAs($employee)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.employees.create'))->assertForbidden();
        $this->actingAs($employee)->post(route('admin.employees.store'), [])->assertForbidden();
        $this->actingAs($employee)->get(route('admin.employees.show', $other))->assertForbidden();
    }

    public function test_resend_invite_only_works_while_invitation_is_pending(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $pending = $this->createEmployeeUser([], ['email_verified_at' => null]);

        $this->actingAs($admin)->post(route('admin.employees.resend-invite', $pending));

        Mail::assertSent(EmployeeInvitationMail::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.employees.index'))->assertRedirect(route('login'));
    }
}
