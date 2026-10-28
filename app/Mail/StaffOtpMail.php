<?php

namespace App\Mail;

use App\Models\User;
use App\Services\ClientOtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The password reset code for an Admin or Employee.
 */
class StaffOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your password reset code');
    }

    public function content(): Content
    {
        $body = "Dear {$this->user->name},\n\nYour password reset code is {$this->code}. This code is valid for ".ClientOtpService::EXPIRES_IN_MINUTES." minutes. Please do not share it with anyone.\n\nYours sincerely,\n".config('app.firm_name');

        return new Content(
            view: 'emails.client-otp',
            with: ['messageBody' => $body],
        );
    }
}
