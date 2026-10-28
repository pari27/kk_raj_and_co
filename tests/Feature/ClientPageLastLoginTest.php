<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPageLastLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_client_page_shows_when_the_client_last_signed_in(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $client = Customer::factory()->create(['last_login_at' => now(), 'last_login_device' => 'Firefox on Windows']);

        $this->actingAs($admin)->get(route('customers.show', $client))
            ->assertOk()
            ->assertSee('Last login')
            ->assertSee('Today,')
            ->assertSee('Firefox on Windows')
            ->assertDontSee('Not recorded');
    }

    public function test_it_says_so_when_the_client_has_never_signed_in(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $client = Customer::factory()->create(['last_login_at' => null]);

        $this->actingAs($admin)->get(route('customers.show', $client))
            ->assertOk()
            ->assertSee('Has not signed in yet');
    }
}
