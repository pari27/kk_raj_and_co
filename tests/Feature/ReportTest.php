<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * An enquiry of 3,000 holding two services (1,000 + 2,000), with 1,500 paid.
     *
     * @return array{Enquiry, Ticket, Ticket, Customer}
     */
    private function enquiryWithPayment(?User $assignee = null): array
    {
        $customer = Customer::factory()->create(['name' => 'Report Client']);
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id, 'total' => 3000, 'created_at' => now()]);

        $small = Ticket::factory()->create([
            'enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $assignee?->id,
            'service_id' => Service::factory()->create(['name' => 'Small Service'])->id, 'total' => 1000,
        ]);
        $big = Ticket::factory()->create([
            'enquiry_id' => $enquiry->id, 'customer_id' => $customer->id, 'assigned_to' => $assignee?->id,
            'service_id' => Service::factory()->create(['name' => 'Big Service'])->id, 'total' => 2000,
        ]);

        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 1500, 'mode' => 'UPI', 'paid_at' => now(), 'reference' => 'REF-ONE']);

        return [$enquiry, $small, $big, $customer];
    }

    public function test_every_report_renders_for_an_admin(): void
    {
        $this->enquiryWithPayment();
        $admin = $this->admin();

        foreach (array_keys(\App\Services\ReportService::REPORTS) as $report) {
            $this->actingAs($admin)->get(route('reports.index', ['report' => $report]))->assertOk()->assertSee('View report');
        }
    }

    public function test_the_report_picker_lists_the_groups_and_descriptions(): void
    {
        $this->actingAs($this->admin())->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Money')
            ->assertSee('Payments received in the period')
            ->assertSee('Unpaid fees, oldest first')
            ->assertSee('Client history');
    }

    public function test_fees_collected_lists_payments_and_the_total(): void
    {
        $this->enquiryWithPayment();

        $this->actingAs($this->admin())->get(route('reports.index', ['report' => 'fees_collected']))
            ->assertOk()
            ->assertSee('REF-ONE')
            ->assertSee('Report Client')
            ->assertSee('₹1,500')
            ->assertSee('Total for the period · 1 payment');
    }

    public function test_service_summary_splits_an_enquiry_across_its_services(): void
    {
        $this->enquiryWithPayment();

        // 1,500 paid of 3,000: Small (1/3) = 1,000 billed / 500 received; Big (2/3) = 2,000 / 1,000.
        $response = $this->actingAs($this->admin())->get(route('reports.index', ['report' => 'service_summary', 'from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]));

        $response->assertOk()->assertSee('Small Service')->assertSee('Big Service')->assertSee('₹3,000')->assertSee('₹1,500');
    }

    public function test_fees_pending_shows_only_completed_services_with_a_balance(): void
    {
        [$enquiry, $small] = $this->enquiryWithPayment();
        $small->update(['status' => 'Task Completed', 'completed_at' => now()->subDays(20)]);

        $this->actingAs($this->admin())->get(route('reports.index', ['report' => 'fees_pending']))
            ->assertOk()
            ->assertSee($enquiry->number)
            ->assertSee('₹1,500')
            ->assertSee('20');
    }

    public function test_pending_documents_lists_missing_and_rejected_documents(): void
    {
        [, $small] = $this->enquiryWithPayment();
        $missing = ServiceDocument::factory()->create(['service_id' => $small->service_id, 'name' => 'Missing Doc', 'is_mandatory' => true]);
        $rejected = ServiceDocument::factory()->create(['service_id' => $small->service_id, 'name' => 'Rejected Doc', 'is_mandatory' => false]);
        $done = ServiceDocument::factory()->create(['service_id' => $small->service_id, 'name' => 'Done Doc']);

        TicketDocument::factory()->create(['ticket_id' => $small->id, 'service_document_id' => $rejected->id, 'status' => 'Rejected', 'verified_at' => now()->subDays(3)]);
        TicketDocument::factory()->create(['ticket_id' => $small->id, 'service_document_id' => $done->id, 'status' => 'Verified']);

        $this->actingAs($this->admin())->get(route('reports.index', ['report' => 'pending_documents']))
            ->assertOk()
            ->assertSee('Missing Doc')
            ->assertSee('Rejected Doc (re-upload)')
            ->assertDontSee('Done Doc');
    }

    public function test_client_history_opens_on_the_first_client_and_can_switch(): void
    {
        [, , , $customer] = $this->enquiryWithPayment();
        $admin = $this->admin();

        // With no client picked it opens on the first one (alphabetically) rather than an empty page.
        $this->actingAs($admin)->get(route('reports.index', ['report' => 'client_history']))
            ->assertOk()
            ->assertSee('Report Client')
            ->assertSee('Small Service');

        $this->actingAs($admin)->get(route('reports.index', ['report' => 'client_history', 'client_id' => $customer->id]))
            ->assertOk()
            ->assertSee('Small Service')
            ->assertSee('Big Service')
            ->assertSee('Client since');
    }

    public function test_employee_report_only_includes_their_own_services_and_payments(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $this->enquiryWithPayment($employee);

        $otherCustomer = Customer::factory()->create(['name' => 'Someone Else']);
        $otherEnquiry = Enquiry::factory()->create(['customer_id' => $otherCustomer->id, 'total' => 900]);
        Ticket::factory()->create(['enquiry_id' => $otherEnquiry->id, 'customer_id' => $otherCustomer->id, 'assigned_to' => null]);
        Payment::factory()->create(['enquiry_id' => $otherEnquiry->id, 'amount' => 900, 'paid_at' => now(), 'reference' => 'HIDDEN-REF']);

        $this->actingAs($employee)->get(route('reports.index', ['report' => 'fees_collected']))
            ->assertOk()
            ->assertSee('REF-ONE')
            ->assertDontSee('HIDDEN-REF');

        $this->actingAs($employee)->get(route('reports.index', ['report' => 'services_by_status', 'from' => now()->subYear()->toDateString()]))
            ->assertOk()
            ->assertSee('Small Service')
            ->assertDontSee('Someone Else');
    }

    public function test_excel_export_is_a_real_xlsx_with_dates_shown_as_dd_mm_yyyy(): void
    {
        $this->enquiryWithPayment();

        $response = $this->actingAs($this->admin())->get(route('reports.export', ['report' => 'fees_collected']));
        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $file = $response->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Fees collected', $sheet);
        $this->assertStringContainsString('Received by', $sheet);
        $this->assertStringContainsString('REF-ONE', $sheet);
        $this->assertStringContainsString('<v>1500</v>', $sheet);

        $zip = new \ZipArchive;
        $zip->open($file);
        $this->assertStringContainsString('formatCode="dd\-mm\-yyyy"', $zip->getFromName('xl/styles.xml'));
        $zip->close();
        $this->assertStringContainsString('Total for the period', $sheet);
        // The date is a real Excel date (style 6 = dd-mm-yyyy), not text and not "########".
        $serial = intdiv(strtotime(now()->format('Y-m-d').' UTC'), 86400) + 25569;
        $this->assertStringContainsString('s="6"><v>'.$serial.'</v>', $sheet);
    }

    public function test_client_history_export_puts_the_client_name_in_the_title(): void
    {
        [, , , $customer] = $this->enquiryWithPayment();

        $response = $this->actingAs($this->admin())->get(route('reports.export', ['report' => 'client_history', 'client_id' => $customer->id]));
        $response->assertOk();

        $zip = new \ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Client history (Report Client)', $sheet);
        $this->assertStringContainsString('Small Service', $sheet);
    }

    public function test_long_reports_are_paged_at_25_rows(): void
    {
        $customer = Customer::factory()->create();
        Ticket::factory()->count(30)->create(['customer_id' => $customer->id, 'assigned_to' => null]);

        $this->actingAs($this->admin())->get(route('reports.index', ['report' => 'services_by_status']))
            ->assertOk()
            ->assertSee('Page 1 of 2 · 25 rows per page');
    }
}
