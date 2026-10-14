<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $employee,
        public string $setPasswordUrl,
    ) {}

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        $template = EmailTemplate::query()->where('key', 'employee_welcome')->first();

        if (! $template) {
            return [
                'subject' => 'Welcome to '.config('app.name').' — set up your account',
                'body' => "Hi {$this->employee->name},\n\nAn account has been created for you on ".config('app.name').". Open the link below to set your password and sign in.\n\n{$this->setPasswordUrl}",
            ];
        }

        return $template->render([
            'employee_name' => $this->employee->name,
            'set_password_url' => $this->setPasswordUrl,
            'firm_name' => config('app.name'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-invitation',
            with: ['messageBody' => $this->rendered()['body']],
        );
    }
}
