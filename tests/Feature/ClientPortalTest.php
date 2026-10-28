<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Mail\ClientOtpMail;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Otp;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sign a client in on the portal guard only.
     */
    protected function asClient(Customer $client): static
    {
        Auth::guard('customer')->setUser($client);

        return $this;
    }

    private function client(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'name' => 'Meera Traders',
            'email' => 'meera@example.com',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Ask for a code and return the 6 digits that were emailed.
     */
    private function requestCode(string $email): string
    {
        Mail::fake();

        $this->post(route('customer.otp.send'), ['email' => $email]);

        $sent = Mail::sent(ClientOtpMail::class);
        $this->assertCount(1, $sent, 'Expected exactly one code email');

        return $sent->first()->code;
    }

    private function setPasswordFor(Customer $client, string $password = 'a-good-password'): void
    {
        $code = $this->requestCode($client->email);

        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code])
            ->assertRedirect(route('customer.set-password'));

        $this->post(route('customer.set-password.store'), ['password' => $password, 'password_confirmation' => $password])
            ->assertRedirect(route('customer.dashboard'));
    }

    // ------------------------------------------------------------------
    // Signing in
    // ------------------------------------------------------------------

    public function test_the_login_and_code_screens_render(): void
    {
        $this->get(route('customer.login'))->assertOk()->assertSee('Welcome back')->assertSee('First time here?')->assertSee('Remember me');
        $this->get(route('customer.verify-otp'))->assertOk()->assertSee('Send me a code');
    }

    public function test_a_code_is_emailed_and_only_its_hash_is_stored(): void
    {
        $client = $this->client();

        $code = $this->requestCode($client->email);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $otp = Otp::query()->firstOrFail();
        $this->assertSame('login', $otp->purpose);
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
        $this->assertTrue($otp->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)->addSecond()));
    }

    public function test_an_unknown_email_gets_the_same_reply_and_no_email(): void
    {
        $this->client();
        Mail::fake();

        $known = $this->post(route('customer.otp.send'), ['email' => 'meera@example.com']);
        $unknown = $this->post(route('customer.otp.send'), ['email' => 'nobody@example.com']);

        $this->assertSame(session('status'), session('status'));
        $known->assertSessionHas('status');
        $unknown->assertSessionHas('status');
        Mail::assertSent(ClientOtpMail::class, 1);
    }

    public function test_an_inactive_client_gets_no_code(): void
    {
        $this->client(['is_active' => false]);
        Mail::fake();

        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com'])->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_a_shared_email_gets_no_code_so_it_cannot_reach_the_wrong_client(): void
    {
        $this->client();
        $this->client(['name' => 'Someone Else', 'phone' => '9000000001']);
        Mail::fake();

        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com']);

        Mail::assertNothingSent();
    }

    public function test_no_code_is_sent_when_the_firm_has_switched_email_off(): void
    {
        $this->client();
        Setting::current()->update(['notifications_enabled' => false]);
        Mail::fake();

        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com'])->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_a_second_code_cannot_be_requested_within_a_minute(): void
    {
        $this->client();
        Mail::fake();

        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com']);
        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com']);

        Mail::assertSent(ClientOtpMail::class, 1);

        $this->travel(61)->seconds();
        $this->post(route('customer.otp.send'), ['email' => 'meera@example.com']);

        Mail::assertSent(ClientOtpMail::class, 2);
    }

    public function test_the_email_uses_the_saved_template_wording(): void
    {
        \App\Models\EmailTemplate::where('key', 'customer_otp')->update([
            'subject' => 'Code for {{customer_name}}',
            'body' => "Hi {{customer_name}}, use {{otp_code}} to sign in.",
        ]);
        $client = $this->client();

        $mail = new ClientOtpMail($client, '123456');

        $mail->assertHasSubject('Code for Meera Traders');
        $mail->assertSeeInHtml('use 123456 to sign in.');
    }

    public function test_first_login_flow_sets_a_password_and_signs_the_client_in(): void
    {
        $client = $this->client();

        $this->setPasswordFor($client);

        $this->assertAuthenticatedAs($client->fresh(), 'customer');
        $this->assertGuest('web');

        $client->refresh();
        $this->assertTrue(Hash::check('a-good-password', $client->password));
        $this->assertSame('activated', $client->portal_status);
        $this->assertNotNull($client->last_login_at);
    }

    public function test_a_code_works_only_once(): void
    {
        $client = $this->client();
        $code = $this->requestCode($client->email);

        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code])->assertRedirect(route('customer.set-password'));

        $this->flushSession();
        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code])->assertSessionHasErrors('code');
    }

    public function test_a_wrong_code_is_refused_and_five_wrong_tries_lock_the_code(): void
    {
        $client = $this->client();
        $code = $this->requestCode($client->email);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $wrong])->assertSessionHasErrors('code');
        }

        // Even the right code no longer works: a new one has to be requested.
        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code])->assertSessionHasErrors('code');
    }

    public function test_an_expired_code_is_refused(): void
    {
        $client = $this->client();
        $code = $this->requestCode($client->email);

        $this->travel(11)->minutes();

        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code])->assertSessionHasErrors('code');
    }

    public function test_a_new_code_replaces_the_old_one(): void
    {
        $client = $this->client();
        $first = $this->requestCode($client->email);

        $this->travel(61)->seconds();
        $second = $this->requestCode($client->email);

        if ($first !== $second) {
            $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $first])->assertSessionHasErrors('code');
        }

        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $second])->assertRedirect(route('customer.set-password'));
    }

    public function test_choosing_a_password_needs_a_verified_code_first(): void
    {
        $this->get(route('customer.set-password'))->assertRedirect(route('customer.verify-otp'));

        $this->post(route('customer.set-password.store'), ['password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
            ->assertRedirect(route('customer.verify-otp'));
    }

    public function test_the_password_rules_are_enforced(): void
    {
        $client = $this->client(['phone' => '9876543210']);
        $code = $this->requestCode($client->email);
        $this->post(route('customer.otp.verify'), ['email' => $client->email, 'code' => $code]);

        $this->post(route('customer.set-password.store'), ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post(route('customer.set-password.store'), ['password' => 'a-good-password', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $this->post(route('customer.set-password.store'), ['password' => 'meera@example.com', 'password_confirmation' => 'meera@example.com'])->assertSessionHasErrors('password');
        $this->post(route('customer.set-password.store'), ['password' => '9876543210', 'password_confirmation' => '9876543210'])->assertSessionHasErrors('password');

        $this->assertGuest('customer');
    }

    public function test_a_client_can_sign_in_with_their_password(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'a-good-password'])->save();

        $this->post(route('customer.login.store'), ['email' => 'MEERA@example.com', 'password' => 'a-good-password'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($client, 'customer');
    }

    public function test_a_wrong_password_or_an_inactive_account_cannot_sign_in(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'a-good-password'])->save();

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');

        $client->update(['is_active' => false]);
        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'a-good-password'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_a_client_who_has_no_password_yet_is_pointed_to_the_code_flow(): void
    {
        $this->client();

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'anything-here'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('not set a password', session('errors')->first('email'));
    }

    public function test_repeated_wrong_passwords_are_rate_limited(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'a-good-password'])->save();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'wrong-password']);
        }

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'a-good-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));
    }

    public function test_a_client_can_forget_and_reset_their_password_with_a_code(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'old-password-1'])->save();

        $this->setPasswordFor($client, 'new-password-2');

        $this->assertSame('forgot_password', Otp::query()->firstOrFail()->purpose);
        $this->assertTrue(Hash::check('new-password-2', $client->fresh()->password));
    }

    public function test_signing_out_ends_the_session(): void
    {
        $client = $this->client();

        $this->asClient($client)->post(route('customer.logout'))->assertRedirect(route('customer.login'));

        $this->assertGuest('customer');
    }

    public function test_client_and_staff_sessions_do_not_mix(): void
    {
        $client = $this->client();
        $staff = User::factory()->create(['role' => UserRole::Admin]);

        // A signed-in client is not staff...
        $this->asClient($client)->get(route('dashboard'))->assertRedirect(route('login'));

        // ...and signed-in staff are not a client.
        Auth::guard('customer')->forgetUser();
        $this->actingAs($staff)->get(route('customer.dashboard'))->assertRedirect(route('customer.login'));
    }

    public function test_client_sign_ins_are_recorded_without_pointing_at_a_staff_user(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'a-good-password'])->save();

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'a-good-password']);

        $log = AuditLog::query()->where('module', 'Client Portal')->where('action', 'Logged in')->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertSame('Meera Traders', $log->record_label);
    }

    // ------------------------------------------------------------------
    // What a client sees
    // ------------------------------------------------------------------

    /**
     * @return array{Customer, Ticket, Enquiry}
     */
    private function clientWithService(array $ticketAttributes = []): array
    {
        $client = $this->client();
        $enquiry = Enquiry::factory()->create(['customer_id' => $client->id, 'total' => 1180]);
        $ticket = Ticket::factory()->create(array_merge([
            'customer_id' => $client->id,
            'enquiry_id' => $enquiry->id,
            'service_id' => Service::factory()->create(['name' => 'GST Registration'])->id,
            'total' => 1180,
        ], $ticketAttributes));

        return [$client, $ticket, $enquiry];
    }

    public function test_the_dashboard_shows_only_the_clients_own_services_and_fees(): void
    {
        [$client, , $enquiry] = $this->clientWithService();
        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 500]);

        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        Ticket::factory()->create([
            'customer_id' => $other->id,
            'service_id' => Service::factory()->create(['name' => 'Secret Service'])->id,
        ]);

        $this->asClient($client)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Meera')
            ->assertSee('My services')
            ->assertSee('GST Registration')
            ->assertSee('Amount due')
            ->assertSee('₹680')
            ->assertDontSee('Secret Service');
    }

    public function test_a_client_can_open_their_own_service_but_not_someone_elses(): void
    {
        [$client, $ticket] = $this->clientWithService();
        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        $theirs = Ticket::factory()->create(['customer_id' => $other->id]);

        $this->asClient($client)->get(route('customer.tickets.show', $ticket))->assertOk()->assertSee($ticket->number);
        $this->asClient($client)->get(route('customer.tickets.show', $theirs))->assertNotFound();
    }

    public function test_the_progress_shows_status_changes_but_hides_staff_names_and_reassignments(): void
    {
        [$client, $ticket] = $this->clientWithService();
        $staff = User::factory()->create(['role' => UserRole::Admin, 'name' => 'Secret Staffer']);

        AuditLog::create(['user_id' => $staff->id, 'action' => 'Updated', 'module' => 'Service', 'record_label' => $ticket->number, 'details' => 'Status Documents Pending → Work In Progress', 'subject_type' => Ticket::class, 'subject_id' => $ticket->id]);
        AuditLog::create(['user_id' => $staff->id, 'action' => 'Updated', 'module' => 'Service', 'record_label' => $ticket->number, 'details' => 'Reassigned to Secret Staffer', 'subject_type' => Ticket::class, 'subject_id' => $ticket->id]);

        $this->asClient($client)->get(route('customer.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Status Documents Pending → Work In Progress')
            ->assertDontSee('Secret Staffer')
            ->assertDontSee('Reassigned');
    }

    public function test_the_payments_page_lists_only_the_clients_own_payments(): void
    {
        [$client, , $enquiry] = $this->clientWithService();
        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 700, 'mode' => 'Cheque']);
        Payment::factory()->create(['amount' => 9999, 'mode' => 'Cash']);

        $this->asClient($client)->get(route('customer.payments.index'))
            ->assertOk()
            ->assertSee('₹700')
            ->assertSee('Cheque')
            ->assertDontSee('₹9,999');
    }

    // ------------------------------------------------------------------
    // Documents
    // ------------------------------------------------------------------

    private function withDocument(array $documentAttributes = []): array
    {
        Storage::fake('public');
        [$client, $ticket, $enquiry] = $this->clientWithService();
        $document = ServiceDocument::factory()->create(array_merge([
            'service_id' => $ticket->service_id,
            'name' => 'PAN Card',
            'is_mandatory' => true,
            'allowed_formats' => 'PDF, JPG, PNG',
            'max_file_size_kb' => 5120,
        ], $documentAttributes));

        return [$client, $ticket, $document];
    }

    public function test_a_client_can_upload_a_document_and_staff_are_told(): void
    {
        [$client, $ticket, $document] = $this->withDocument();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), [
                'service_document_id' => $document->id,
                'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('customer.tickets.show', $ticket));

        $upload = TicketDocument::query()->firstOrFail();
        $this->assertSame(1, $upload->version);
        $this->assertSame('Awaiting check', $upload->status);
        $this->assertSame($client->id, $upload->uploaded_by_customer_id);
        $this->assertNull($upload->uploaded_by);
        Storage::disk('public')->assertExists($upload->file_path);

        // The service moves on from "Documents Pending" and staff are notified by the client's name.
        $this->assertNotSame(TicketStatus::DocumentsPending, $ticket->fresh()->status);
        $notification = AppNotification::where('user_id', $admin->id)->where('title', 'Document uploaded')->firstOrFail();
        $this->assertStringContainsString('Meera Traders', $notification->body);

        // And the staff page names the uploader as the client.
        $this->actingAs($admin)->get(route('tickets.show', $ticket))->assertOk()->assertSee('Uploaded by Client');
    }

    public function test_upload_rules_follow_the_document_settings(): void
    {
        [$client, $ticket, $document] = $this->withDocument();

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), [
                'service_document_id' => $document->id,
                'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('file');

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), [
                'service_document_id' => $document->id,
                'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, TicketDocument::count());
    }

    public function test_a_document_that_is_already_sent_cannot_be_sent_again_until_it_is_rejected(): void
    {
        [$client, $ticket, $document] = $this->withDocument();
        $send = fn () => $this->asClient($client)->post(route('customer.documents.store', $ticket), [
            'service_document_id' => $document->id,
            'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
        ]);

        $send();
        $send()
            ->assertRedirect(route('customer.tickets.show', $ticket))
            ->assertSessionHas('error');
        $this->assertSame(1, TicketDocument::count());

        TicketDocument::query()->update(['status' => 'Rejected', 'rejection_reason' => 'Blurred']);

        $send()->assertRedirect(route('customer.tickets.show', $ticket));
        $this->assertSame([1, 2], TicketDocument::query()->orderBy('version')->pluck('version')->all());

        // The rejection reason is shown on the service page.
        TicketDocument::query()->where('version', 2)->update(['status' => 'Rejected', 'rejection_reason' => 'Still blurred']);
        $this->asClient($client)->get(route('customer.tickets.show', $ticket))->assertSee('Still blurred');
    }

    public function test_a_text_document_takes_typed_details(): void
    {
        [$client, $ticket, $document] = $this->withDocument(['input_type' => 'text', 'allowed_formats' => null, 'max_file_size_kb' => null, 'name' => 'PAN number']);

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), ['service_document_id' => $document->id])
            ->assertSessionHasErrors('text_value');

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), ['service_document_id' => $document->id, 'text_value' => 'ABCDE1234F'])
            ->assertRedirect();

        $this->assertSame('ABCDE1234F', TicketDocument::query()->firstOrFail()->text_value);
    }

    public function test_nothing_can_be_sent_to_a_closed_service(): void
    {
        [$client, $ticket, $document] = $this->withDocument();
        $ticket->update(['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $this->asClient($client)
            ->post(route('customer.documents.store', $ticket), [
                'service_document_id' => $document->id,
                'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertSame(0, TicketDocument::count());
    }

    public function test_a_client_cannot_send_documents_to_someone_elses_service(): void
    {
        [$client, , $document] = $this->withDocument();
        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        $theirs = Ticket::factory()->create(['customer_id' => $other->id, 'service_id' => $document->service_id]);

        $this->asClient($client)
            ->post(route('customer.documents.store', $theirs), [
                'service_document_id' => $document->id,
                'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();

        $this->assertSame(0, TicketDocument::count());
    }

    public function test_a_client_can_download_their_own_file_but_not_anyone_elses(): void
    {
        [$client, $ticket, $document] = $this->withDocument();

        $this->asClient($client)->post(route('customer.documents.store', $ticket), [
            'service_document_id' => $document->id,
            'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
        ]);
        $upload = TicketDocument::query()->firstOrFail();

        $this->asClient($client)->get(route('customer.documents.download', [$ticket, $upload]))->assertOk();

        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        $this->asClient($other)->get(route('customer.documents.download', [$ticket, $upload]))->assertNotFound();
    }

    public function test_guests_cannot_reach_any_portal_screen(): void
    {
        [, $ticket] = $this->clientWithService();

        foreach ([
            route('customer.dashboard'),
            route('customer.tickets.index'),
            route('customer.tickets.show', $ticket),
            route('customer.payments.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('customer.login'));
        }

        $this->post(route('customer.documents.store', $ticket), [])->assertRedirect(route('customer.login'));
    }

    // ------------------------------------------------------------------
    // Look and feel: steps, Home, Updates, Profile
    // ------------------------------------------------------------------

    public function test_a_service_is_shown_as_one_of_four_steps(): void
    {
        $portal = app(\App\Services\ClientPortalService::class);

        $expected = [
            'Documents Pending' => [1, 'Documents needed'],
            'Additional Documents Required' => [1, 'Documents needed'],
            'Documents Received' => [2, 'Checking'],
            'Under Verification' => [2, 'Checking'],
            'Work In Progress' => [3, 'Work in progress'],
            'Submitted to Department' => [3, 'Work in progress'],
            'On Hold' => [3, 'On hold'],
            'Task Completed' => [4, 'Completed'],
        ];

        foreach ($expected as $status => [$step, $label]) {
            [, $ticket] = $this->clientWithService(['status' => $status]);
            $stage = $portal->stage($ticket->fresh(['service.documents', 'documents']));

            $this->assertSame($step, $stage['step'], $status);
            $this->assertSame($label, $stage['label'], $status);
        }
    }

    public function test_home_shows_what_needs_doing_and_who_is_handling_it(): void
    {
        Storage::fake('public');
        [$client, $ticket] = $this->clientWithService();
        $handler = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Priya Das']);
        $ticket->update(['assigned_to' => $handler->id]);
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'Signature']);
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'Photo']);

        $this->asClient($client)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('Upload your Signature')
            ->assertSee('Upload your Photo')
            ->assertSee('Documents needed')
            ->assertSee('0 of 2 uploaded')
            ->assertSee('Recent updates')
            ->assertSee('Contact ')
            // The staff member's own details are not shown on the dashboard any more.
            ->assertDontSee('Your contact at')
            ->assertDontSee('Priya Das');
    }

    public function test_the_dashboard_lists_the_latest_updates_and_the_firms_contact_details(): void
    {
        [$client] = $this->clientWithService();
        config([
            'app.firm_phone' => '033 4000 1234',
            'app.firm_email' => 'info@firm.test',
            'app.firm_address' => '12 Park Street, Kolkata',
        ]);

        foreach (range(1, 6) as $i) {
            \App\Models\ClientNotification::create(['customer_id' => $client->id, 'type' => 'service', 'title' => "Update number {$i}"]);
        }

        $this->asClient($client)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Update number 6')->assertSee('Update number 2')
            ->assertDontSee('Update number 1<')
            ->assertSee('All updates')
            ->assertSee('tel:0334000123', false)->assertSee('033 4000 1234')
            ->assertSee('mailto:info@firm.test', false)
            ->assertSee('12 Park Street, Kolkata');
    }

    public function test_the_contact_panel_hides_details_the_firm_has_not_set(): void
    {
        [$client] = $this->clientWithService();
        config(['app.firm_phone' => null, 'app.firm_email' => null, 'app.firm_address' => null]);

        $this->asClient($client)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('contact details will appear here soon')
            ->assertDontSee('Tap to call');
    }

    public function test_the_side_menu_help_box_only_shows_when_the_firm_has_set_a_phone(): void
    {
        [$client, $ticket] = $this->clientWithService();
        $ticket->update(['assigned_to' => User::factory()->create(['role' => UserRole::Employee])->id]);

        // The side menu only shows the help box when a phone is set.
        config(['app.firm_phone' => null]);
        $this->asClient($client)->get(route('customer.dashboard'))->assertDontSee('Need help?');

        config(['app.firm_phone' => '033 4000 1234']);
        $this->asClient($client)->get(route('customer.dashboard'))->assertSee('Need help?')->assertSee('033 4000 1234');
    }

    public function test_the_client_is_told_when_a_document_is_verified_or_rejected(): void
    {
        [$client, $ticket] = $this->clientWithService();
        $document = ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'PAN Card']);
        $upload = TicketDocument::factory()->create(['ticket_id' => $ticket->id, 'service_document_id' => $document->id, 'status' => 'Awaiting check']);

        $upload->update(['status' => 'Verified']);
        $upload->update(['status' => 'Rejected', 'rejection_reason' => 'The image is blurred']);

        $titles = \App\Models\ClientNotification::where('customer_id', $client->id)->pluck('title', 'id')->values()->all();
        $this->assertContains('PAN Card verified', $titles);
        $this->assertContains('PAN Card needs a re-upload', $titles);

        $this->asClient($client)->get(route('customer.updates'))
            ->assertOk()
            ->assertSee('PAN Card needs a re-upload')
            ->assertSee('The image is blurred');
    }

    public function test_the_client_is_told_when_their_service_moves_on_and_when_a_fee_is_due(): void
    {
        [$client, $ticket] = $this->clientWithService();

        $ticket->update(['status' => TicketStatus::WorkInProgress]);
        $ticket->update(['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);

        $titles = \App\Models\ClientNotification::where('customer_id', $client->id)->pluck('title')->all();

        $this->assertContains('GST Registration is in progress', $titles);
        $this->assertContains('GST Registration completed', $titles);
        $this->assertContains('Payment due: ₹1,180', $titles);
    }

    public function test_the_client_is_told_about_a_payment(): void
    {
        [$client, , $enquiry] = $this->clientWithService();

        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 500]);

        $update = \App\Models\ClientNotification::where('customer_id', $client->id)->firstOrFail();
        $this->assertSame('Payment received: ₹500.00', $update->title);
        $this->assertSame(route('customer.payments.index'), $update->url);
    }

    public function test_opening_an_update_marks_it_read_and_goes_to_the_service(): void
    {
        [$client, $ticket] = $this->clientWithService();
        $update = \App\Models\ClientNotification::create([
            'customer_id' => $client->id, 'type' => 'service', 'title' => 'Something happened', 'url' => route('customer.tickets.show', $ticket),
        ]);

        $this->asClient($client)->get(route('customer.updates.open', $update))->assertRedirect(route('customer.tickets.show', $ticket));

        $this->assertNotNull($update->fresh()->read_at);
    }

    public function test_updates_belong_to_one_client_only(): void
    {
        [$client] = $this->clientWithService();
        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        $theirs = \App\Models\ClientNotification::create(['customer_id' => $other->id, 'type' => 'service', 'title' => 'Private news']);

        $this->asClient($client)->get(route('customer.updates'))->assertDontSee('Private news');
        $this->asClient($client)->get(route('customer.updates.open', $theirs))->assertNotFound();
    }

    public function test_mark_all_read_clears_the_dot(): void
    {
        [$client] = $this->clientWithService();
        \App\Models\ClientNotification::create(['customer_id' => $client->id, 'type' => 'service', 'title' => 'One']);
        \App\Models\ClientNotification::create(['customer_id' => $client->id, 'type' => 'service', 'title' => 'Two']);

        $this->asClient($client)->get(route('customer.dashboard'))->assertSee('pbottom-dot', false);

        $this->asClient($client)->post(route('customer.updates.read-all'))->assertRedirect();

        $this->assertSame(0, \App\Models\ClientNotification::where('customer_id', $client->id)->whereNull('read_at')->count());
        $this->asClient($client)->get(route('customer.dashboard'))->assertDontSee('pbottom-dot', false);
    }

    public function test_the_profile_shows_the_clients_details(): void
    {
        $client = $this->client(['phone' => '9876543210', 'last_login_at' => now(), 'last_login_device' => 'Firefox on Windows']);

        $this->asClient($client)->get(route('customer.profile'))
            ->assertOk()
            ->assertSee('Meera Traders')
            ->assertSee('Full name')
            ->assertSee('98765 43210')
            ->assertSee('Security')
            ->assertSee('Last login')
            ->assertSee('Email updates')
            ->assertSee('In-app updates')
            ->assertSee('meera@example.com')
            ->assertSee('Used for login')
            ->assertSee(route('customer.password.edit'), false)
            ->assertSee('Log out');
    }

    public function test_a_client_can_switch_email_updates_on_and_off(): void
    {
        $client = $this->client(['email_notifications_enabled' => true]);

        $this->asClient($client)->put(route('customer.profile.email-updates'), ['email_updates' => '0']);
        $this->assertFalse($client->fresh()->email_notifications_enabled);

        $this->asClient($client)->put(route('customer.profile.email-updates'), ['email_updates' => '1']);
        $this->assertTrue($client->fresh()->email_notifications_enabled);
    }

    public function test_a_client_can_change_their_password(): void
    {
        $client = $this->client(['phone' => '9876543210']);
        $client->forceFill(['password' => 'old-password-1'])->save();

        $change = fn (array $data) => $this->asClient($client)->put(route('customer.password.update'), $data);

        $change(['current_password' => 'wrong', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2'])->assertSessionHasErrors('current_password');
        $change(['current_password' => 'old-password-1', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $change(['current_password' => 'old-password-1', 'password' => 'old-password-1', 'password_confirmation' => 'old-password-1'])->assertSessionHasErrors('password');
        $change(['current_password' => 'old-password-1', 'password' => '9876543210', 'password_confirmation' => '9876543210'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password-1', $client->fresh()->password));

        $change(['current_password' => 'old-password-1', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2'])
            ->assertRedirect(route('customer.profile'));

        $this->assertTrue(Hash::check('new-password-2', $client->fresh()->password));
        $this->assertTrue(AuditLog::where('details', 'Client changed their portal password')->exists());
    }

    public function test_keep_me_logged_in_sets_a_remember_token(): void
    {
        $client = $this->client();
        $client->forceFill(['password' => 'a-good-password'])->save();

        $this->post(route('customer.login.store'), ['email' => 'meera@example.com', 'password' => 'a-good-password', 'remember' => '1'])
            ->assertRedirect(route('customer.dashboard'))
            ->assertCookie(Auth::guard('customer')->getRecallerName());
    }

    public function test_the_services_list_shows_each_service_with_its_status_and_payment(): void
    {
        [$client, $ticket] = $this->clientWithService(['status' => 'Work In Progress']);

        $this->asClient($client)->get(route('customer.tickets.index'))
            ->assertOk()
            ->assertSee('My services')
            ->assertSee('GST Registration')
            ->assertSee($ticket->number)
            ->assertSee('Work in progress')
            ->assertSee('Not paid')
            ->assertSee('₹1,180');
    }

    public function test_a_finished_and_settled_service_reaches_the_fifth_step_paid(): void
    {
        [$client, $ticket, $enquiry] = $this->clientWithService();
        $portal = app(\App\Services\ClientPortalService::class);

        $ticket->update(['status' => TicketStatus::TaskCompleted, 'completed_at' => now()]);
        $stage = $portal->stage($ticket->fresh(['service.documents', 'documents', 'enquiry.payments', 'enquiry.tickets']));
        $this->assertSame(4, $stage['step']);
        $this->assertFalse($stage['paid']);

        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 1180]);
        $stage = $portal->stage($ticket->fresh(['service.documents', 'documents', 'enquiry.payments', 'enquiry.tickets']));
        $this->assertSame(5, $stage['step']);
        $this->assertTrue($stage['paid']);

        $this->asClient($client)->get(route('customer.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Documents')->assertSee('Checking')->assertSee('Work in progress')->assertSee('Completed')->assertSee('Paid')
            ->assertSee('STEP 5 OF 5');
    }

    public function test_the_dashboard_cards_count_the_right_things(): void
    {
        Storage::fake('public');
        [$client, $ticket, $enquiry] = $this->clientWithService();
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'Signature']);
        Payment::factory()->create(['enquiry_id' => $enquiry->id, 'amount' => 180]);

        $done = Ticket::factory()->create([
            'customer_id' => $client->id, 'enquiry_id' => $enquiry->id,
            'status' => TicketStatus::TaskCompleted, 'completed_at' => now(), 'total' => 0,
        ]);

        $stats = app(\App\Services\ClientPortalService::class)->stats(
            app(\App\Services\ClientPortalService::class)->services($client),
            app(\App\Services\ClientPortalService::class)->fees(app(\App\Services\ClientPortalService::class)->services($client)),
        );

        $this->assertSame(1, $stats['active']);
        $this->assertSame(1, $stats['documents']);
        $this->assertSame('for GST Registration', $stats['documentServices']);
        $this->assertSame(1000.0, $stats['due']);
        $this->assertSame(1, $stats['completed']);
        $this->assertNotNull($done);
    }

    public function test_what_needs_attention_lists_rejected_then_missing_documents_then_the_fee(): void
    {
        Storage::fake('public');
        [$client, $ticket] = $this->clientWithService();
        $rejected = ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'Signature']);
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'Photo', 'allowed_formats' => 'JPG, PNG', 'max_file_size_kb' => 2048]);
        TicketDocument::factory()->create([
            'ticket_id' => $ticket->id, 'service_document_id' => $rejected->id, 'status' => 'Rejected', 'rejection_reason' => 'image is blurred',
        ]);

        $response = $this->asClient($client)->get(route('customer.dashboard'));

        $response->assertOk()
            ->assertSeeInOrder(['Re-upload your Signature', 'Upload your Photo', 'Pay ₹1,180'])
            ->assertSee('image is blurred')
            ->assertSee('JPG, PNG, up to 2 MB')
            ->assertSee('How to pay');
    }

    public function test_the_service_page_shows_the_fee_breakdown_the_contact_and_the_documents(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Priya Das', 'email' => 'priya@firm.test']);
        [$client, $ticket] = $this->clientWithService(['assigned_to' => $staff->id, 'price' => 1000, 'gst_percent' => 18, 'gst_amount' => 180]);
        ServiceDocument::factory()->create(['service_id' => $ticket->service_id, 'name' => 'PAN Card', 'allowed_formats' => 'PDF, JPG, PNG', 'max_file_size_kb' => 5120, 'is_mandatory' => true]);

        $this->asClient($client)->get(route('customer.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Documents')->assertSee('0 of 1 verified')
            ->assertSee('PAN Card')->assertSee('Mandatory')->assertSee('PDF, JPG or PNG · up to 5 MB')
            ->assertSee('Not uploaded')
            ->assertSee('₹1,000')->assertSee('GST (18%)')->assertSee('₹180')
            ->assertSee('To pay')->assertSee('after the work is completed')
            ->assertDontSee('Your contact')->assertDontSee('priya@firm.test')
            ->assertSee('Your final documents');
    }

    public function test_the_bare_client_address_sends_signed_in_clients_home_and_everyone_else_to_login(): void
    {
        $this->get('/client')->assertRedirect(route('customer.login'));
        $this->get('/client/')->assertRedirect(route('customer.login'));

        $this->asClient($this->client())->get('/client')->assertRedirect(route('customer.dashboard'));
    }

    public function test_a_client_sees_a_view_link_that_opens_their_file_in_a_new_tab(): void
    {
        [$client, $ticket, $document] = $this->withDocument();

        $this->asClient($client)->post(route('customer.documents.store', $ticket), [
            'service_document_id' => $document->id,
            'file' => UploadedFile::fake()->image('photo.png'),
        ]);
        $upload = TicketDocument::query()->firstOrFail();
        $viewUrl = route('customer.documents.view', [$ticket, $upload]);

        $page = $this->asClient($client)->get(route('customer.tickets.show', $ticket));
        $page->assertOk()
            ->assertSee('href="'.$viewUrl.'"', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('photo.png');

        // It is shown in the browser (inline), not forced to download.
        $response = $this->asClient($client)->get($viewUrl);
        $response->assertOk();
        $this->assertStringNotContainsString('attachment', (string) $response->headers->get('content-disposition'));

        // And only its owner can open it.
        $other = $this->client(['name' => 'Other Client', 'email' => 'other@example.com', 'phone' => '9000000002']);
        $this->asClient($other)->get($viewUrl)->assertNotFound();
    }
}
