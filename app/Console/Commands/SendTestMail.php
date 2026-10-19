<?php

namespace App\Console\Commands;

use App\Mail\FormalNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {to? : Address to send to (defaults to MAIL_TEST_RECIPIENT)}';

    protected $description = 'Send one test email to check that mail delivery is set up correctly';

    public function handle(): int
    {
        $to = $this->argument('to') ?: config('mail.test_recipient');

        if (! $to) {
            $this->error('No address given and MAIL_TEST_RECIPIENT is not set.');

            return self::FAILURE;
        }

        $this->line('Mailer: '.config('mail.default'));

        try {
            Mail::to($to)->send(new FormalNotificationMail(
                'Test email from '.config('app.firm_name'),
                "Dear Sir or Madam,\n\nThis is a test message to confirm that email delivery from ".config('app.firm_name')." is working.\n\nYours sincerely,\n".config('app.firm_name'),
            ));
        } catch (Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sent to {$to}".(config('mail.default') === 'log' ? ' (written to storage/logs/laravel.log only, since the mailer is "log")' : '.'));

        return self::SUCCESS;
    }
}
