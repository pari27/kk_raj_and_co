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
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ])->assertRedirect(route('admin.settings.firm-profile'));

        $setting = Setting::current()->fresh();
        $this->assertSame('Sharma & Co', $setting->firm_name);
        $this->assertSame('12 Park Street, Kolkata', $setting->firm_address);
        $this->assertNotNull($setting->firm_logo_path);
        Storage::disk('public')->assertExists($setting->firm_logo_path);
    }

    public function test_name_and_address_are_required_and_the_logo_must_be_an_image(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => '',
            'firm_address' => '',
            'logo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['firm_name', 'firm_address', 'logo']);
    }

    public function test_the_logo_can_be_removed_to_go_back_to_the_default(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'Sharma & Co',
            'firm_address' => 'Kolkata',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $path = Setting::current()->fresh()->firm_logo_path;

        $this->actingAs($admin)->put(route('admin.settings.firm-profile.update'), [
            'firm_name' => 'Sharma & Co',
            'firm_address' => 'Kolkata',
            'remove_logo' => '1',
        ]);

        $this->assertNull(Setting::current()->fresh()->firm_logo_path);
        Storage::disk('public')->assertMissing($path);
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