<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_customer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Meera Traders',
            'phone' => '9123456789',
            'email' => 'accounts@meeratraders.in',
            'is_active' => '1',
        ]);

        $customer = Customer::query()->where('phone', '9123456789')->firstOrFail();
        $response->assertRedirect(route('customers.show', $customer));
        $this->assertSame('Meera Traders', $customer->name);
    }

    public function test_employee_can_also_create_a_customer(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($employee)->post(route('customers.store'), [
            'name' => 'Rahul Sharma',
            'phone' => '9876543210',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Customer::count());
    }

    public function test_search_finds_customers_by_name_phone_or_email(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Customer::factory()->create(['name' => 'Meera Traders', 'phone' => '9123456789']);
        Customer::factory()->create(['name' => 'Rahul Sharma', 'phone' => '9876543210']);

        $response = $this->actingAs($admin)->get(route('customers.search', ['q' => '9123456789']));

        $response->assertOk();
        $response->assertJsonCount(1, 'customers');
        $response->assertJsonFragment(['name' => 'Meera Traders']);
    }

    public function test_quick_store_creates_a_customer_and_returns_it_as_json(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->postJson(route('customers.quick-store'), [
            'name' => 'Ankit Verma',
            'phone' => '7001234567',
        ]);

        $response->assertOk();
        $response->assertJsonPath('customer.name', 'Ankit Verma');
        $this->assertSame(1, Customer::count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }
}
