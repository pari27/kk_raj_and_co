<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PageTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_pages_are_titled_firm_role_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'is_active' => true]);

        $this->actingAs($admin)->get(route('dashboard'))->assertSee('<title>KK Raj &amp; Co - Admin - Dashboard</title>', false);
        $this->actingAs($admin)->get(route('reports.index'))->assertSee('<title>KK Raj &amp; Co - Admin - Reports</title>', false);
        $this->actingAs($employee)->get(route('dashboard'))->assertSee('<title>KK Raj &amp; Co - Employee - Dashboard</title>', false);
        $this->actingAs($employee)->get(route('tickets.index'))->assertSee('<title>KK Raj &amp; Co - Employee - Services</title>', false);
    }

    public function test_client_pages_are_titled_firm_page(): void
    {
        Auth::guard('customer')->setUser(Customer::factory()->create(['is_active' => true]));

        $this->get(route('customer.dashboard'))->assertSee('<title>KK Raj &amp; Co - Dashboard</title>', false);
        $this->get(route('customer.payments.index'))->assertSee('<title>KK Raj &amp; Co - Payments</title>', false);
    }

    public function test_the_sign_in_pages_are_titled_firm_page(): void
    {
        $this->get(route('login'))->assertSee('<title>KK Raj &amp; Co - Log in</title>', false);
        $this->get(route('customer.login'))->assertSee('<title>KK Raj &amp; Co - Client Login</title>', false);
    }
}
