<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FirmProfileTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    }

    public function test_the_client_login_page_shows_the_firms_saved_phone_number(): void
    {
        Setting::current()->update(['firm_phone' => '+91 98765 43210']);
        Setting::applyFirmProfile();

        $this->get(route('customer.login'))
            ->assertOk()
            ->assertSee('Need help? Call')
            ->assertSee('+91 98765 43210')
            ->assertDontSee('XXXXXXXXXX');
    }

    public function test_the_client_login_page_hides_the_help_line_when_no_phone_is_saved(): void
    {
        Setting::current()->update(['firm_phone' => null]);
        config(['app.firm_phone' => null]);

        $this->get(route('customer.login'))->assertOk()->assertDontSee('Need help?');
    }

    public function test_the_firm_profile_page_shows_the_saved_details(): void
    {
        Setting::current()->update(['firm_name' => 'Sharma & Co', 'firm_address' => '12 Park Street, Kolkata']);

        $this->actingAs($this->admin())->get(route('admin.settings.firm-profile'))
            ->assertOk()
            ->assertSee('Sharma &amp; Co', false)
            ->assertSee('12 Park Street, Kolkata');
    }

    public function test_an_admin_can_save_the_name_address_and_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'Sharma & Co',
            'firm_address' => '12 Park Street, Kolkata',
            'firm_phone' => '033 4000 1234', 'firm_email' => 'info@firm.com',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ])->assertRedirect(route('admin.settings.firm-profile'));

        $setting = Setting::current()->fresh();
        $this->assertSame('Sharma & Co', $setting->firm_name);
        $this->assertSame('12 Park Street, Kolkata', $setting->firm_address);
        $this->assertNotNull($setting->firm_logo_path);
        Storage::disk('public')->assertExists($setting->firm_logo_path);
    }

    public function test_the_firms_phone_and_email_can_be_saved_but_not_left_empty(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'Sharma & Co',
            'firm_address' => 'Kolkata',
            'firm_phone' => '033 4000 1234',
            'firm_email' => 'info@firm.com',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect(route('admin.settings.firm-profile'));

        $setting = Setting::current()->fresh();
        $this->assertSame('033 4000 1234', $setting->firm_phone);
        $this->assertSame('info@firm.com', $setting->firm_email);

        // Phone and email can no longer be left empty.
        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'Sharma & Co', 'firm_address' => 'Kolkata',
        ])->assertSessionHasErrors(['firm_phone', 'firm_email']);
        $this->assertSame('033 4000 1234', Setting::current()->fresh()->firm_phone);
    }

    public function test_a_bad_phone_or_email_is_rejected(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'X', 'firm_address' => 'Y', 'firm_phone' => 'call me', 'firm_email' => 'not-an-email'
        ])->assertSessionHasErrors(['firm_phone', 'firm_email']);
    }
    public function test_name_and_address_are_required_and_the_logo_must_be_an_image(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => '',
            'firm_address' => '',
            'logo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['firm_name', 'firm_address', 'logo']);
    }

    public function test_a_logo_is_required_until_one_has_been_saved(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $details = ['firm_name' => 'Sharma & Co', 'firm_address' => 'Kolkata', 'firm_phone' => '033 4000 1234', 'firm_email' => 'info@firm.com'];

        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), $details)
            ->assertSessionHasErrors('logo');

        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), $details + ['logo' => UploadedFile::fake()->image('logo.png')])
            ->assertSessionHasNoErrors();

        // Once a logo is saved, leaving the box empty keeps it.
        $path = Setting::current()->fresh()->firm_logo_path;
        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), $details)
            ->assertSessionHasNoErrors();
        $this->assertSame($path, Setting::current()->fresh()->firm_logo_path);
    }

    public function test_an_employee_cannot_change_the_firm_profile(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($employee)->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'X',
            'firm_address' => 'Y',
        ])->assertForbidden();
    }
}