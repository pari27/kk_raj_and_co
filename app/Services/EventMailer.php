<?php

namespace App\Services;

use App\Mail\FormalNotificationMail;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the formal event emails (to admins, employees and clients), using the wording
 * from the matching email template. It follows the same rules as every other email:
 * nothing goes out when notifications are switched off, and in Test mode everything is
 * redirected to the test recipient. A mail failure never stops the action that caused it.
 */
class EventMailer
{
    /**
     * Tell a new client they now have the client portal, and how to get in.
     */
    public static function welcomeClient(Customer $customer): void
    {
        self::send('client_welcome', $customer, ['customer_name' => $customer->name], route('customer.login'), 'Open the client portal');
    }

    /**
     * Ask a client for the documents the services in a new enquiry need, with a button to the portal.
     *
     * @param  iterable<int, \App\Models\Ticket>  $tickets
     */
    public static function requestDocuments(Customer $customer, string $enquiryNumber, iterable $tickets): void
    {
        $blocks = collect($tickets)
            ->map(function ($ticket): ?string {
                $ticket->loadMissing('service.documents');
                $documents = $ticket->service?->documents ?? collect();

                if ($documents->isEmpty()) {
                    return null;
                }

                $lines = $documents->map(fn ($document): string => '- '.$document->name.($document->is_mandatory ? '' : ' (optional)'));

                return $ticket->service->name.' ('.$ticket->number.")\n".$lines->implode("\n");
            })
            ->filter()
            ->values();

        if ($blocks->isEmpty()) {
            return;
        }

        self::send('client_documents_requested', $customer, [
            'customer_name' => $customer->name,
            'enquiry_number' => $enquiryNumber,
            'documents_list' => $blocks->implode("\n\n"),
        ], route('customer.dashboard'), 'Upload your documents');
    }

    /**
     * @param  array<string, string>  $values  Values for the template's {{placeholders}}
     * @param  bool  $essential  The client must act on this (e.g. a rejected document), so it is sent
     *                           even if they switched "Email updates" off in their profile.
     */
    public static function send(string $templateKey, User|Customer|null $to, array $values, ?string $url = null, ?string $linkLabel = null, bool $essential = false): void
    {
        if (! $to || ! $to->email || ! $to->is_active || ! Setting::notificationsEnabled()) {
            return;
        }

        // The software team (Super Admin) never receives the firm's emails.
        if ($to instanceof User && $to->isSuperAdmin()) {
            return;
        }

        if ($to instanceof User && $to->id === Auth::guard('web')->id()) {
            return; // The person who did it does not need an email about it.
        }

        if ($to instanceof Customer && ! $essential && ! $to->email_notifications_enabled) {
            return;
        }

        $template = EmailTemplate::query()->where('key', $templateKey)->first();

        if (! $template) {
            return;
        }

        $recipient = Setting::testModeEnabled() ? config('mail.test_recipient') : $to->email;

        if (! $recipient) {
            return;
        }

        $rendered = $template->render($values + ['firm_name' => config('app.firm_name')]);

        $message = Mail::to($recipient);

        // Test mode already sends everything to one address, so a copy would only duplicate it.
        $copyTo = config('mail.copy_to');

        if ($to instanceof Customer && $copyTo && ! Setting::testModeEnabled() && strcasecmp($copyTo, $recipient) !== 0) {
            $message->bcc($copyTo);
        }

        try {
            $message->send(new FormalNotificationMail($rendered['subject'], $rendered['body'], $url, $linkLabel));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
