<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Mail\EmployeeInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => UserRole::Admin, 'is_active' => true], $attributes));
    }

    public function test_the_list_shows_real_admins_only(): void
    {
        $me = $this->admin(['name' => 'Paramita Admin']);
        $this->admin(['name' => 'Second Admin']);
        User::factory()->create(['role' => UserRole::Employee, 'name' => 'Just An Employee']);

        $this->actingAs($me)->get(route('admin.admin-accounts.index'))
            ->assertOk()
            ->assertSee('Paramita Admin')
            ->assertSee('Second Admin')
            ->assertDontSee('Just An Employee');
    }

    public function test_an_admin_can_set_the_new_admins_password_and_they_can_sign_in_straight_away(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('admin.admin-accounts.store'), [
            'name' => 'Neha Verma',
            'email' => 'neha@firm.com',
            'is_active' => '1',
            'password' => 'Welcome#2026',
            'password_confirmation' => 'Welcome#2026',
        ])->assertRedirect(route('admin.admin-accounts.index'));

        $new = User::where('email', 'neha@firm.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $new->role);
        $this->assertTrue($new->hasSetPassword());
        Mail::assertNothingSent();

        auth()->logout();
        $this->post(route('login.store'), ['email' => 'neha@firm.com', 'password' => 'Welcome#2026'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($new);
    }

    public function test_a_password_is_required(): void
    {
        $this->actingAs($this->admin())->post(route('admin.admin-accounts.store'), [
            'name' => 'Neha Verma',
            'email' => 'neha@firm.com',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'neha@firm.com']);
    }

    public function test_the_password_must_match_and_be_long_enough(): void
    {
        $me = $this->admin();

        $this->actingAs($me)->post(route('admin.admin-accounts.store'), [
            'name' => 'A', 'email' => 'a@firm.com', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->actingAs($me)->post(route('admin.admin-accounts.store'), [
            'name' => 'A', 'email' => 'a@firm.com', 'password' => 'Welcome#2026', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'a@firm.com']);
    }

    public function test_the_email_must_be_new_and_valid(): void
    {
        $me = $this->admin(['email' => 'taken@firm.com']);

        $this->actingAs($me)->post(route('admin.admin-accounts.store'), ['name' => 'X', 'email' => 'taken@firm.com', 'password' => 'Welcome#2026', 'password_confirmation' => 'Welcome#2026'])
            ->assertSessionHasErrors('email');
        $this->actingAs($me)->post(route('admin.admin-accounts.store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_the_add_and_edit_pages_open(): void
    {
        $me = $this->admin();
        $other = $this->admin(['name' => 'Other Admin']);

        $this->actingAs($me)->get(route('admin.admin-accounts.create'))->assertOk()->assertSee('Full name');
        $this->actingAs($me)->get(route('admin.admin-accounts.edit', $other))->assertOk()->assertSee('Other Admin');
    }

    public function test_an_admin_can_edit_and_deactivate_another_admin(): void
    {
        $me = $this->admin();
        $other = $this->admin(['name' => 'Old Name']);

        $this->actingAs($me)->put(route('admin.admin-accounts.update', $other), [
            'name' => 'New Name',
            'email' => $other->email,
            'is_active' => '0',
        ])->assertRedirect(route('admin.admin-accounts.index'));

        $this->assertSame('New Name', $other->fresh()->name);
        $this->assertFalse($other->fresh()->is_active);
    }

    public function test_you_cannot_deactivate_yourself(): void
    {
        $me = $this->admin();
        $this->admin();

        $this->actingAs($me)->put(route('admin.admin-accounts.update', $me), [
            'name' => $me->name,
            'email' => $me->email,
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($me->fresh()->is_active);
    }

    public function test_the_last_active_admin_cannot_be_deactivated(): void
    {
        $me = $this->admin();
        $inactive = $this->admin(['is_active' => false]);

        // Only $me is active: deactivating someone else is fine, but nobody may remove the last active Admin.
        $this->actingAs($inactive)->put(route('admin.admin-accounts.update', $me), [
            'name' => $me->name,
            'email' => $me->email,
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($me->fresh()->is_active);
    }

    public function test_an_employee_cannot_reach_the_admin_accounts(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($employee)->get(route('admin.admin-accounts.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('admin.admin-accounts.store'), ['name' => 'A', 'email' => 'a@b.com'])->assertForbidden();
    }

    public function test_an_employee_account_cannot_be_edited_as_an_admin(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.admin-accounts.edit', $employee))->assertNotFound();
    }

    public function test_the_invitation_can_be_sent_again_until_a_password_is_set(): void
    {
        Mail::fake();
        $me = $this->admin();
        $pending = $this->admin(['email_verified_at' => null]);

        $this->actingAs($me)->post(route('admin.admin-accounts.resend-invite', $pending))->assertRedirect();
        Mail::assertSent(EmployeeInvitationMail::class, 1);

        $done = $this->admin(['email_verified_at' => now()]);
        $this->actingAs($me)->post(route('admin.admin-accounts.resend-invite', $done))->assertRedirect();
        Mail::assertSent(EmployeeInvitationMail::class, 1);
    }
}
