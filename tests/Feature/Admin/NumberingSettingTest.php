<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingSettingTest extends TestCase
{
    use RefreshDatabase;

    private function createEnquiry(User $admin): Ticket
    {
        $this->actingAs($admin)->post(route('enquiries.store'), [
            'customer_id' => Customer::factory()->create()->id,
            'services' => [['service_id' => Service::factory()->create()->id]],
        ]);

        return Ticket::query()->latest('id')->firstOrFail();
    }

    public function test_new_services_use_the_default_srv_prefix(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertMatchesRegularExpression('/^SRV-\d{4}-0001$/', $this->createEnquiry($admin)->number);
    }

    public function test_admin_can_change_the_prefix_and_new_services_use_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.numbering.update'), ['service_prefix' => 'wrk', 'enquiry_prefix' => 'inq'])
            ->assertRedirect(route('admin.settings.firm-profile'));

        $this->assertSame('WRK', Setting::servicePrefix());
        $this->assertSame('INQ', Setting::enquiryPrefix());
        $this->assertMatchesRegularExpression('/^WRK-\d{4}-0001$/', $this->createEnquiry($admin)->number);
        $this->assertMatchesRegularExpression('/^INQ-\d{4}-0001$/', \App\Models\Enquiry::query()->latest('id')->value('number'));
    }

    public function test_prefix_must_be_two_to_six_letters_or_numbers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        foreach (['', 'A', 'TOOLONG1', 'AB-C', 'A B'] as $invalid) {
            $this->actingAs($admin)
                ->put(route('admin.settings.numbering.update'), ['service_prefix' => $invalid, 'enquiry_prefix' => 'ENQ'])
                ->assertSessionHasErrors('service_prefix');
        }

        $this->assertSame('SRV', Setting::servicePrefix());
    }

    public function test_enquiry_and_service_prefixes_must_differ(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.numbering.update'), ['service_prefix' => 'ABC', 'enquiry_prefix' => 'abc'])
            ->assertSessionHasErrors('enquiry_prefix');
    }

    public function test_employees_cannot_change_the_prefix(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($employee)
            ->put(route('admin.settings.numbering.update'), ['service_prefix' => 'ABC', 'enquiry_prefix' => 'DEF'])
            ->assertForbidden();
    }
}
