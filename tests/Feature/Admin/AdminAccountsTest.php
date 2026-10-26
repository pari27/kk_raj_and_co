<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true]);
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => UserRole::Admin, 'is_active' => true], $attributes));
    }

    public function test_the_list_shows_real_admins_only_and_never_the_super_admin(): void
    {
        $this->admin(['name' => 'First Admin']);
        $this->admin(['name' => 'Second Admin']);
        User::factory()->create(['role' => UserRole::Employee, 'name' => 'Just An Employee']);
        $super = User::factory()->create(['role' => UserRole::SuperAdmin, 'is_active' => true, 'name' => 'Netiapps Support', 'email' => 'support@netiapps.example']);

        $this->actingAs($super)->get(route('admin.admin-accounts.index'))
            ->assertOk()
            ->assertSee('First Admin')
            ->assertSee('Second Admin')
            ->assertDontSee('Just An Employee')
            ->assertDontSee('support@netiapps.example');
    }

    public function test_only_the_super_admin_can_open_or_use_admin_accounts(): void
    {
        $admin = $this->admin();
        $other = $this->admin();
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        foreach ([$admin, $employee] as $user) {
            $this->actingAs($user)->get(route('admin.admin-accounts.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.admin-accounts.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.admin-accounts.edit', $other))->assertForbidden();
            $this->actingAs($user)->post(route('admin.admin-accounts.store'), ['name' => 'A', 'email' => 'a@b.com', 'password' => 'Welcome#2026', 'password_confirmation' => 'Welcome#2026'])->assertForbidden();
            $this->actingAs($user)->put(route('admin.admin-accounts.update', $other), ['name' => 'X', 'email' => $other->email])->assertForbidden();
        }
    }

    public function test_the_admin_accounts_menu_item_is_only_shown_to_the_super_admin(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertDontSee(route('admin.admin-accounts.index'));
        $this->actingAs($this->superAdmin())->get(route('dashboard'))->assertSee(route('admin.admin-accounts.index'));
    }

    public function test_the_add_and_edit_pages_open(): void
    {
        $other = $this->admin(['name' => 'Other Admin']);
        $super = $this->superAdmin();

        $this->actingAs($super)->get(route('admin.admin-accounts.create'))->assertOk()->assertSee('Full name');
        $this->actingAs($super)->get(route('admin.admin-accounts.edit', $other))->assertOk()->assertSee('Other Admin');
    }

    public function test_the_super_admin_can_add_an_admin_with_a_password_and_they_can_sign_in_straight_away(): void
    {
        $this->actingAs($this->superAdmin())->post(route('admin.admin-accounts.store'), [
            'name' => 'Neha Verma',
            'email' => 'Neha@Firm.com',
            'is_active' => '1',
            'password' => 'Welcome#2026',
            'password_confirmation' => 'Welcome#2026',
        ])->assertRedirect(route('admin.admin-accounts.index'));

        $new = User::where('email', 'neha@firm.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $new->role);
        $this->assertTrue($new->hasSetPassword());

        auth()->logout();
        $this->post(route('login.store'), ['email' => 'neha@firm.com', 'password' => 'Welcome#2026'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($new);
    }

    public function test_a_password_is_required_and_must_match_and_be_long_enough(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('admin.admin-accounts.store'), ['name' => 'A', 'email' => 'a@firm.com'])
            ->assertSessionHasErrors('password');

        $this->actingAs($super)->post(route('admin.admin-accounts.store'), [
            'name' => 'A', 'email' => 'a@firm.com', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->actingAs($super)->post(route('admin.admin-accounts.store'), [
            'name' => 'A', 'email' => 'a@firm.com', 'password' => 'Welcome#2026', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'a@firm.com']);
    }

    public function test_the_email_must_be_new_and_valid(): void
    {
        $super = $this->superAdmin();
        $this->admin(['email' => 'taken@firm.com']);

        $this->actingAs($super)->post(route('admin.admin-accounts.store'), ['name' => 'X', 'email' => 'taken@firm.com', 'password' => 'Welcome#2026', 'password_confirmation' => 'Welcome#2026'])
            ->assertSessionHasErrors('email');
        $this->actingAs($super)->post(route('admin.admin-accounts.store'), ['name' => '', 'email' => 'not-an-email', 'password' => 'Welcome#2026', 'password_confirmation' => 'Welcome#2026'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_the_super_admin_can_edit_and_deactivate_an_admin_but_not_the_last_active_one(): void
    {
        $super = $this->superAdmin();
        $first = $this->admin(['name' => 'Old Name']);
        $second = $this->admin();

        $this->actingAs($super)->put(route('admin.admin-accounts.update', $first), [
            'name' => 'New Name', 'email' => $first->email, 'is_active' => '0',
        ])->assertRedirect(route('admin.admin-accounts.index'));

        $this->assertSame('New Name', $first->fresh()->name);
        $this->assertFalse($first->fresh()->is_active);

        // $second is now the only active Admin: the firm must always keep one.
        $this->actingAs($super)->put(route('admin.admin-accounts.update', $second), [
            'name' => $second->name, 'email' => $second->email, 'is_active' => '0',
        ])->assertSessionHasErrors('is_active');
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_an_employee_account_cannot_be_edited_as_an_admin(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($this->superAdmin())->get(route('admin.admin-accounts.edit', $employee))->assertNotFound();
    }
}
