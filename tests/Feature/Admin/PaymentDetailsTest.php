<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    }

    public function test_the_payment_details_page_opens_for_an_admin(): void
    {
        $this->actingAs($this->admin())->get(route('admin.settings.payment-details'))
            ->assertOk()->assertSee('UPI ID')->assertSee('IFSC code');
    }

    public function test_an_admin_can_save_payment_details(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.payment-details.update'), [
            'payment_upi_id' => 'kkraj@hdfcbank',
            'payment_bank_account_name' => 'KK Raj & Co',
            'payment_bank_account_number' => '5020 0012 3456 78',
            'payment_bank_ifsc' => 'hdfc0001234',
            'payment_bank_branch' => 'HDFC Bank, Park Street',
            'payment_instructions' => 'Cheques in favour of the firm.',
        ])->assertRedirect(route('admin.settings.payment-details'));

        $setting = Setting::current()->fresh();
        $this->assertSame('kkraj@hdfcbank', $setting->payment_upi_id);
        $this->assertSame('HDFC0001234', $setting->payment_bank_ifsc);
        $this->assertSame('5020 0012 3456 78', $setting->payment_bank_account_number);
    }

    public function test_bad_values_are_rejected_and_every_bank_field_is_required(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.payment-details.update'), [
            'payment_upi_id' => 'not a upi id',
            'payment_bank_account_number' => 'ABC',
            'payment_bank_ifsc' => '1234',
        ])->assertSessionHasErrors(['payment_upi_id', 'payment_bank_account_number', 'payment_bank_ifsc']);

        $this->actingAs($admin)->put(route('admin.settings.payment-details.update'), [])
            ->assertSessionHasErrors(['payment_upi_id', 'payment_bank_account_name', 'payment_bank_account_number', 'payment_bank_ifsc', 'payment_bank_branch']);
    }

    public function test_an_employee_cannot_open_or_change_payment_details(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($employee)->get(route('admin.settings.payment-details'))->assertForbidden();
        $this->actingAs($employee)->put(route('admin.settings.payment-details.update'), [])->assertForbidden();
    }
}