<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddClientLookupTest extends TestCase
{
    use RefreshDatabase;

    private function staff(UserRole $role = UserRole::Admin): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_the_add_client_page_asks_for_the_mobile_number_first(): void
    {
        $html = $this->actingAs($this->staff())->get(route('customers.create'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'id="clientName"'), strpos($html, 'id="clientPhone"'));
        $this->assertStringContainsString('id="clientLookupBanner"', $html);
        $this->assertStringContainsString('id="clientSubmit"', $html);
    }

    public function test_the_lookup_returns_an_existing_client_with_a_link_to_their_page(): void
    {
        $client = Customer::factory()->create(['name' => 'Meera Traders', 'phone' => '9876543210', 'email' => 'meera@example.com']);

        $this->actingAs($this->staff(UserRole::Employee))
            ->getJson(route('customers.lookup', ['phone' => '9876543210']))
            ->assertOk()
            ->assertJsonPath('customer.name', 'Meera Traders')
            ->assertJsonPath('customer.email', 'meera@example.com')
            ->assertJsonPath('customer.url', route('customers.show', $client));
    }

    public function test_the_lookup_finds_nothing_for_a_new_number(): void
    {
        $this->actingAs($this->staff())
            ->getJson(route('customers.lookup', ['phone' => '9123456789']))
            ->assertOk()
            ->assertJsonPath('customer', null);
    }

    public function test_the_same_mobile_number_still_cannot_be_saved_twice(): void
    {
        Customer::factory()->create(['phone' => '9876543210']);

        $this->actingAs($this->staff())->post(route('customers.store'), [
            'name' => 'Someone Else',
            'phone' => '9876543210',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(1, Customer::count());
    }
}
