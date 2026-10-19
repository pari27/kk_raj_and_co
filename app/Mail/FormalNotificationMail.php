<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A formal, event-driven email. The wording comes from an email template
 * (Settings > Email Templates); this only gives it the letterhead.
 */
class FormalNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $messageBody,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.formal',
            with: [
                'messageBody' => $this->messageBody,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
            ],
        );
    }
}
