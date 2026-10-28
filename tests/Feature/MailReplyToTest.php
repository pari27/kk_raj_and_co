<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailReplyToTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_email_carries_the_configured_reply_to_address(): void
    {
        Mail::raw('Hello', fn ($message) => $message->to('client@example.test')->subject('Hi'));

        /** @var ArrayTransport $transport */
        $transport = Mail::mailer()->getSymfonyTransport();
        $message = $transport->messages()->last()->getOriginalMessage();

        $this->assertSame('replies@firm.test', $message->getReplyTo()[0]->getAddress());
    }
}
