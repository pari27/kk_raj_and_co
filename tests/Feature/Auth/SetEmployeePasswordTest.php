<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SetEmployeePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrlFor(User $employee): string
    {
        return URL::temporarySignedRoute('employees.set-password', now()->addDays(7), ['employee' => $employee]);
    }

    public function test_employee_can_set_their_password_via_signed_link(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'email_verified_at' => null]);

        $response = $this->get($this->signedUrlFor($employee));
        $response->assertOk();

        $response = $this->post($this->signedUrlFor($employee), [
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $employee->refresh();
        $this->assertTrue(Hash::check('new-secret-123', $employee->password));
        $this->assertTrue($employee->hasSetPassword());
        $this->assertAuthenticatedAs($employee);
    }

    public function test_link_without_valid_signature_is_rejected(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'email_verified_at' => null]);

        $this->get(route('employees.set-password', ['employee' => $employee]))->assertForbidden();
    }

    public function test_cannot_reuse_link_after_password_already_set(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'email_verified_at' => now()]);

        $response = $this->get($this->signedUrlFor($employee));

        $response->assertRedirect(route('login'));
    }
}
