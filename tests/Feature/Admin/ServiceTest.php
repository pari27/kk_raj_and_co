<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_services_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Service::factory()->create(['name' => 'GST Return Filing']);

        $response = $this->actingAs($admin)->get(route('admin.services.index'));

        $response->assertOk();
        $response->assertSee('GST Return Filing');
    }

    public function test_admin_can_create_a_service_with_documents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'description' => 'Monthly filing',
            'default_price' => 2500,
            'gst_percent' => 18,
            'is_active' => '1',
            'documents' => [
                ['name' => 'PAN Card', 'instructions' => 'Front side scan', 'allowed_formats' => 'PDF, JPG', 'max_file_size_mb' => '5', 'mandatory' => '1'],
                ['name' => 'Bank Statement', 'instructions' => 'Last 6 months', 'mandatory' => ''],
            ],
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('name', 'GST Return Filing')->firstOrFail();
        $this->assertSame('2500.00', $service->default_price);
        $this->assertTrue($service->is_active);
        $this->assertSame($admin->id, $service->created_by);
        $this->assertCount(2, $service->documents);

        $panCard = $service->documents->firstWhere('name', 'PAN Card');
        $this->assertTrue($panCard->is_mandatory);
        $this->assertSame('PDF, JPG', $panCard->allowed_formats);
        $this->assertSame(5120, $panCard->max_file_size_kb);

        $bankStatement = $service->documents->firstWhere('name', 'Bank Statement');
        $this->assertFalse($bankStatement->is_mandatory);
    }

    public function test_creating_a_service_writes_activity_log_entries(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => 2500,
            'gst_percent' => 18,
            'documents' => [
                ['name' => 'PAN Card', 'mandatory' => '1'],
            ],
        ]);

        $service = Service::query()->where('name', 'GST Return Filing')->firstOrFail();
        $logs = $service->activityLogs;

        $this->assertTrue($logs->contains(fn ($log) => $log->action === 'Created'));
        $this->assertTrue($logs->contains(fn ($log) => $log->details === 'Price set to ₹2,950 (GST excluded)'));
        $this->assertTrue($logs->contains(fn ($log) => $log->details === 'Document added: PAN Card (mandatory)'));
    }

    public function test_price_includes_gst_defaults_to_false_when_not_submitted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => 2500,
            'gst_percent' => 18,
        ]);

        $service = Service::query()->where('name', 'GST Return Filing')->firstOrFail();
        $this->assertFalse($service->price_includes_gst);
    }

    public function test_price_includes_gst_can_be_turned_on(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => 2500,
            'gst_percent' => 18,
            'price_includes_gst' => '1',
        ]);

        $service = Service::query()->where('name', 'GST Return Filing')->firstOrFail();
        $this->assertTrue($service->price_includes_gst);
    }

    public function test_total_fee_adds_gst_on_top_when_price_excludes_gst(): void
    {
        $service = Service::factory()->create(['default_price' => 2500, 'gst_percent' => 18, 'price_includes_gst' => false]);

        $this->assertSame(450.0, $service->gstAmount());
        $this->assertSame(2950.0, $service->totalFee());
    }

    public function test_total_fee_equals_price_when_price_includes_gst(): void
    {
        $service = Service::factory()->create(['default_price' => 2950, 'gst_percent' => 18, 'price_includes_gst' => true]);

        $this->assertSame(2950.0, $service->totalFee());
        $this->assertEqualsWithDelta(450.0, $service->gstAmount(), 0.01);
    }

    public function test_creating_a_service_discards_blank_document_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'PAN Card Creation',
            'default_price' => 500,
            'gst_percent' => 18,
            'documents' => [
                ['name' => '', 'instructions' => '', 'mandatory' => ''],
            ],
        ]);

        $service = Service::query()->where('name', 'PAN Card Creation')->firstOrFail();
        $this->assertCount(0, $service->documents);
    }

    public function test_each_service_keeps_its_own_document_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Registration',
            'default_price' => 4000,
            'gst_percent' => 18,
            'documents' => [
                ['name' => 'PAN Card', 'mandatory' => '1'],
            ],
        ]);

        $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'PAN Card Creation',
            'default_price' => 500,
            'gst_percent' => 18,
            'documents' => [
                ['name' => 'PAN Card', 'mandatory' => '1'],
            ],
        ]);

        // Same document name on two services should be two independent rows, not a shared record.
        $this->assertSame(2, ServiceDocument::query()->where('name', 'PAN Card')->count());
    }

    public function test_service_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'default_price' => 2500,
            'gst_percent' => 18,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Service::query()->count());
    }

    public function test_service_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Service::factory()->create(['name' => 'GST Return Filing']);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => 2500,
            'gst_percent' => 18,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_default_price_cannot_be_negative(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => -100,
            'gst_percent' => 18,
        ]);

        $response->assertSessionHasErrors('default_price');
    }

    public function test_default_price_allows_at_most_two_decimal_places(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'GST Return Filing',
            'default_price' => 100.123,
            'gst_percent' => 18,
        ]);

        $response->assertSessionHasErrors('default_price');
    }

    public function test_admin_can_view_the_edit_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['name' => 'GST Return Filing']);

        $response = $this->actingAs($admin)->get(route('admin.services.edit', $service));

        $response->assertOk();
        $response->assertSee('GST Return Filing');
    }

    public function test_admin_can_view_the_show_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['name' => 'GST Return Filing']);
        $service->documents()->create(['name' => 'PAN Card', 'instructions' => 'Front side scan', 'is_mandatory' => true]);

        $response = $this->actingAs($admin)->get(route('admin.services.show', $service));

        $response->assertOk();
        $response->assertSee('GST Return Filing');
        $response->assertSee('PAN Card');
    }

    public function test_admin_can_update_a_service_and_its_documents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['name' => 'GST Return Filing', 'default_price' => 2500]);
        $service->documents()->create(['name' => 'Old Document', 'is_mandatory' => true]);

        $response = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => 'GST Return Filing',
            'default_price' => 2750,
            'gst_percent' => 18,
            'documents' => [
                ['name' => 'New Document', 'mandatory' => '1'],
            ],
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertSame('2750.00', $service->default_price);
        $this->assertCount(1, $service->documents);
        $this->assertSame('New Document', $service->documents->first()->name);
    }

    public function test_updating_the_price_writes_an_activity_log_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['default_price' => 2500, 'gst_percent' => 18]);

        $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'default_price' => 3000,
            'gst_percent' => 18,
        ]);

        $log = $service->activityLogs()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Price ₹2,500.00 → ₹3,000.00', $log->details);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_adding_a_document_on_update_writes_an_activity_log_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();

        $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'default_price' => $service->default_price,
            'gst_percent' => $service->gst_percent,
            'documents' => [
                ['name' => 'New Document', 'mandatory' => ''],
            ],
        ]);

        $log = $service->activityLogs()->first();
        $this->assertNotNull($log);
        $this->assertSame('Document added: New Document (optional)', $log->details);
    }

    public function test_admin_can_toggle_a_service_active(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.services.toggle-active', $service));

        $response->assertRedirect(route('admin.services.show', $service));
        $this->assertFalse($service->refresh()->is_active);

        $log = $service->activityLogs()->first();
        $this->assertSame('Deactivated', $log->action);

        $this->actingAs($admin)->patch(route('admin.services.toggle-active', $service));
        $this->assertTrue($service->refresh()->is_active);
    }

    public function test_employee_cannot_view_or_manage_services(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $service = Service::factory()->create();

        $this->actingAs($employee)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.services.create'))->assertForbidden();
        $this->actingAs($employee)->post(route('admin.services.store'), [
            'name' => 'Should Not Save',
            'default_price' => 100,
            'gst_percent' => 18,
        ])->assertForbidden();
        $this->actingAs($employee)->get(route('admin.services.edit', $service))->assertForbidden();
        $this->actingAs($employee)->patch(route('admin.services.toggle-active', $service))->assertForbidden();

        $this->assertSame(1, Service::query()->count());
        $this->assertTrue($service->refresh()->is_active);
    }

    public function test_show_page_reports_real_ticket_counts_and_fees_for_this_fy(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id]);

        Ticket::factory()->create([
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'status' => 'Work In Progress',
            'total' => 1000,
        ]);
        Ticket::factory()->create([
            'enquiry_id' => $enquiry->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'status' => 'Task Completed',
            'total' => 2000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.services.show', $service));

        $response->assertOk();
        $response->assertViewHas('openTicketsCount', 1);
        $response->assertViewHas('completedTicketsCount', 1);
        $response->assertViewHas('feesThisFy', 2000.0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.services.index'))->assertRedirect(route('login'));
    }
}
