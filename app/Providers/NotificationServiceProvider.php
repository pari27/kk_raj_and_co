<?php

namespace App\Providers;

use App\Enums\TicketStatus;
use App\Models\AppNotification;
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

        Ticket::created(function (Ticket $ticket) use ($actor) {
            Notifier::notify(
                [$ticket->assignedTo],
                'tickets',
                'Service assigned to you',
                "{$actor()} assigned **{$ticket->service?->name}** for {$ticket->customer?->name} to you.",
                route('tickets.show', $ticket), $ticket->number, 'Open service',
            );
        });

        Ticket::updated(function (Ticket $ticket) use ($actor) {
            if ($ticket->wasChanged('assigned_to')) {
                Notifier::notify(
                    [$ticket->assignedTo],
                    'tickets',
                    'Service assigned to you',
                    "{$actor()} assigned **{$ticket->service?->name}** for {$ticket->customer?->name} to you.",
                    route('tickets.show', $ticket), $ticket->number, 'Open service',
                );
            }

            if ($ticket->wasChanged('status') && in_array($ticket->status, TicketStatus::manualOptions(), true)) {
                Notifier::notify(
                    [...Notifier::admins(), $ticket->assignedTo],
                    'tickets',
                    'Status changed',
                    "{$ticket->number} moved to **{$ticket->status->value}** by {$actor()}.",
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
