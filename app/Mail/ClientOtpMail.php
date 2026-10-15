<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one-time login code. Wording comes from the "customer_otp" email template.
 */
class ClientOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $code,
    ) {}

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        $template = EmailTemplate::query()->where('key', 'customer_otp')->first();

        if (! $template) {
            return [
                'subject' => 'Your one-time login code',
                'body' => "Hello {$this->customer->name},\n\nYour one-time login code is {$this->code}. It expires in ".\App\Services\ClientOtpService::EXPIRES_IN_MINUTES." minutes.\n\nRegards,\n".config('app.name'),
            ];
        }

        return $template->render([
            'customer_name' => $this->customer->name,
            'otp_code' => $this->code,
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
            view: 'emails.client-otp',
            with: ['messageBody' => $this->rendered()['body']],
        );
    }
}
