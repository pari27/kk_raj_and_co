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
                'subject' => 'Your '.config('app.firm_name').' account: please set your password',
                'body' => "Dear {$this->employee->name},\n\nAn account has been created for you at ".config('app.firm_name').". To activate it, please use the link below to set your password and sign in.\n\n{$this->setPasswordUrl}\n\nYours sincerely,\n".config('app.firm_name'),
            ];
        }

        return $template->render([
            'employee_name' => $this->employee->name,
            'set_password_url' => $this->setPasswordUrl,
            'firm_name' => config('app.firm_name'),
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
