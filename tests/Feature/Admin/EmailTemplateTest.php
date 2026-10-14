<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Mail\EmployeeInvitationMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_list_shows_the_templates_from_the_database(): void
    {
        EmailTemplate::where('key', 'customer_otp')->update(['subject' => 'Your OTP is ready']);

        $this->actingAs($this->admin())
            ->get(route('admin.settings.email-templates.index'))
            ->assertOk()
            ->assertSee('customer_otp')
            ->assertSee('Your OTP is ready');
    }

    public function test_edit_page_shows_the_saved_text_and_its_placeholders(): void
    {
        $template = EmailTemplate::where('key', 'customer_otp')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.settings.email-templates.edit', $template))
            ->assertOk()
            ->assertSee($template->subject)
            ->assertSee('{{otp_code}}', false);
    }

    public function test_admin_can_save_a_template(): void
    {
        $template = EmailTemplate::where('key', 'payment_received')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.settings.email-templates.update', $template), [
                'subject' => 'Thanks for paying',
                'body' => 'Hi {{customer_name}}, we got {{amount}}.',
            ])
            ->assertRedirect(route('admin.settings.email-templates.index'));

        $this->assertSame('Thanks for paying', $template->fresh()->subject);
    }

    public function test_unknown_placeholders_are_rejected(): void
    {
        $template = EmailTemplate::where('key', 'payment_received')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.settings.email-templates.update', $template), [
                'subject' => 'Hi',
                'body' => 'Hello {{nonsense}}',
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_required_placeholders_cannot_be_removed(): void
    {
        $template = EmailTemplate::where('key', 'employee_welcome')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.settings.email-templates.update', $template), [
                'subject' => 'Welcome',
                'body' => 'Hi {{employee_name}}, welcome aboard.',
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_employee_invitation_uses_the_saved_template(): void
    {
        EmailTemplate::where('key', 'employee_welcome')->update([
            'subject' => 'Join {{firm_name}} today',
            'body' => "Dear {{employee_name}},\n\nSign in here:\n\n{{set_password_url}}",
        ]);

        $employee = User::factory()->create(['role' => UserRole::Employee, 'name' => 'Neha Kapoor']);
        $mail = new EmployeeInvitationMail($employee, 'https://example.test/set-password');

        $mail->assertHasSubject('Join '.config('app.name').' today');
        $mail->assertSeeInHtml('Dear Neha Kapoor,');
        $mail->assertSeeInHtml('https://example.test/set-password');
    }

    public function test_employees_cannot_edit_templates(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $template = EmailTemplate::firstOrFail();

        $this->actingAs($employee)
            ->put(route('admin.settings.email-templates.update', $template), ['subject' => 'x', 'body' => 'y'])
            ->assertForbidden();
    }
}
