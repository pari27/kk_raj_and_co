<?php

namespace App\Providers;

use App\Enums\TicketStatus;
use App\Models\AppNotification;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use App\Services\NotificationService as Notifier;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Raises in-app notifications from model events, so no controller needs to
 * know about them.
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            $view->with([
                'tmUnreadCount' => $user ? AppNotification::where('user_id', $user->id)->whereNull('read_at')->count() : 0,
                'tmRecentNotifications' => $user
                    ? AppNotification::where('user_id', $user->id)->latest()->latest('id')->limit(4)->get()
                    : collect(),
            ]);
        });

        $actor = fn (): string => Auth::user()?->name ?? 'Someone';

        TicketDocument::created(function (TicketDocument $document) use ($actor) {
            $ticket = $document->ticket;
            $name = $document->serviceDocument?->name ?? 'a document';

            Notifier::notify(
                [...Notifier::admins(), $ticket->assignedTo],
                'documents',
                'Document uploaded',
                "{$actor()} uploaded **{$name} (v{$document->version})** for {$ticket->service?->name}. It's waiting for your check.",
                route('tickets.show', $ticket), $ticket->number, 'Review document',
            );
        });

        TicketDocument::updated(function (TicketDocument $document) use ($actor) {
            if ($document->wasChanged('status') && $document->status === 'Verified') {
                $ticket = $document->ticket;
                $name = $document->serviceDocument?->name ?? 'a document';

                Notifier::notify(
                    [...Notifier::admins(), $ticket->assignedTo],
                    'documents',
                    'Document verified',
                    "{$actor()} verified **{$name}** on {$ticket->service?->name}.",
                    route('tickets.show', $ticket), $ticket->number,
                );

                return;
            }

            if (! $document->wasChanged('status') || $document->status !== 'Rejected') {
                return;
            }
            $ticket = $document->ticket;
            $name = $document->serviceDocument?->name ?? 'a document';
            $uploader = $document->uploaded_by ? User::find($document->uploaded_by) : null;

            Notifier::notify(
                [...Notifier::admins(), $ticket->assignedTo, $uploader],
                'documents',
                'Document rejected',
                "{$actor()} rejected **{$name}** on {$ticket->service?->name}: {$document->rejection_reason}",
                route('tickets.show', $ticket), $ticket->number,
            );
        });

        // Tells the new assignee, and every admin (except the assignee, who already got theirs).
        $notifyAssignment = function (Ticket $ticket) use ($actor): void {
            $assignee = $ticket->assignedTo;

            if (! $assignee) {
                return;
            }

            $service = $ticket->service?->name;
            $client = $ticket->customer?->name;
            $url = route('tickets.show', $ticket);

            Notifier::notify(
                [$assignee],
                'tickets',
                'Service assigned to you',
                "{$actor()} assigned **{$service}** for {$client} to you.",
                $url, $ticket->number, 'Open service',
            );

            Notifier::notify(
                array_filter(Notifier::admins(), fn (User $admin) => $admin->id !== $assignee->id),
                'tickets',
                'Service assigned',
                "{$actor()} assigned **{$service}** for {$client} to {$assignee->name}.",
                $url, $ticket->number, 'Open service',
            );
        };

        Ticket::created(fn (Ticket $ticket) => $notifyAssignment($ticket));

        Ticket::updated(function (Ticket $ticket) use ($actor, $notifyAssignment) {
            // Read the assignee fresh: the relation may still hold the previous one.
            $ticket->unsetRelation('assignedTo');

            if ($ticket->wasChanged('assigned_to')) {
                $previous = $ticket->getOriginal('assigned_to') ? User::find($ticket->getOriginal('assigned_to')) : null;

                // An employee reassigned away can no longer open the service, so drop their notifications about it.
                if ($previous?->isEmployee()) {
                    AppNotification::where('user_id', $previous->id)->where('link_text', $ticket->number)->delete();
                }

                $notifyAssignment($ticket);
            }

            if ($ticket->wasChanged('status')) {
                $byHand = request()->routeIs('tickets.update-status');

                Notifier::notify(
                    [...Notifier::admins(), $ticket->assignedTo],
                    'tickets',
                    'Status changed',
                    $byHand
                        ? "{$ticket->number} moved to **{$ticket->status->value}** by {$actor()}."
                        : "{$ticket->number} moved to **{$ticket->status->value}** automatically after document updates.",
                    route('tickets.show', $ticket), $ticket->number,
                );
            }
        });

        Payment::created(function (Payment $payment) {
            $enquiry = $payment->enquiry;
            $amount = '₹'.number_format((float) $payment->amount, 2);

            Notifier::notify(
                Notifier::admins(),
                'payments',
                'Payment received',
                "**{$amount}** by {$payment->mode} from {$enquiry?->customer?->name}.",
                route('enquiries.show', $enquiry), $enquiry?->number,
            );
        });

        Payment::deleted(function (Payment $payment) use ($actor) {
            if (! Auth::user()?->isEmployee()) {
                return;
            }

            $enquiry = $payment->enquiry;
            $amount = '₹'.number_format((float) $payment->amount, 2);

            Notifier::notify(
                Notifier::admins(),
                'payments',
                'Payment deleted',
                "{$actor()} deleted a **{$amount}** {$payment->mode} payment from {$enquiry?->customer?->name}.",
                $enquiry ? route('enquiries.show', $enquiry) : null, $enquiry?->number,
            );
        });

        Customer::created(function (Customer $customer) use ($actor) {
            $creator = Auth::user();

            if (! $creator?->isEmployee()) {
                return;
            }

            $designation = $creator->profile?->designation?->name;
            $contact = implode(', ', array_filter([$customer->phone, $customer->email]));

            Notifier::notify(
                Notifier::admins(),
                'clients',
                'New client added',
                "**{$customer->name}** ({$contact}) was added by {$actor()}".($designation ? ", {$designation}" : '')." · {$creator->email}.",
                route('customers.show', $customer), null, 'View client',
            );
        });

        Enquiry::created(function (Enquiry $enquiry) use ($actor) {
            $total = '₹'.number_format((float) $enquiry->total, 2);

            Notifier::notify(
                Notifier::admins(),
                'enquiries',
                'New enquiry',
                "{$actor()} created an enquiry for {$enquiry->customer?->name}, total **{$total}**.",
                route('enquiries.show', $enquiry), $enquiry->number,
            );
        });

        Event::listen(function (Failed $event) {
            Notifier::notify(
                Notifier::admins(),
                'security',
                'Failed login attempt',
                'Wrong password for '.($event->credentials['email'] ?? 'an unknown account').'.',
                route('admin.audit-log'), null, 'Audit log',
            );
        });
    }
}
