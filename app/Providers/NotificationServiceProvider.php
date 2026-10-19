<?php

namespace App\Providers;

use App\Enums\TicketStatus;
use App\Models\AppNotification;
use App\Models\Customer;
use App\Services\ClientPortalService;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use App\Services\EventMailer;
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
    /**
     * Add an Updates line for the client when their service moves to a new step.
     */
    private function tellClientAboutProgress(Ticket $ticket): void
    {
        $client = $ticket->customer;
        $before = $ticket->getOriginal('status');

        if (! $client || ! $before instanceof TicketStatus) {
            return;
        }

        $step = ClientPortalService::stepFor($ticket->status);

        if ($step === ClientPortalService::stepFor($before) && $ticket->status !== TicketStatus::OnHold) {
            return;
        }

        $service = $ticket->service?->name ?? 'Your service';
        $url = route('customer.tickets.show', $ticket);

        match (true) {
            $ticket->status === TicketStatus::OnHold => ClientPortalService::notify($client, 'service', "{$service} is on hold", 'We will tell you when it moves again.', $url),
            $step === 2 => ClientPortalService::notify($client, 'service', "{$service}: we are checking your documents", 'Thank you for sending them. We will tell you what is next.', $url),
            $step === 3 => ClientPortalService::notify($client, 'service', "{$service} is in progress", 'Our team has started working on it.', $url),
            $step === 4 => ClientPortalService::notify($client, 'service', "{$service} completed", 'Your service is complete.', $url),
            default => null,
        };

        if ($step === 4) {
            EventMailer::send('service_completed', $client, [
                'customer_name' => $client->name,
                'service_number' => $ticket->number,
                'service_name' => $service,
            ], $url, 'View your service');

            $balance = app(ClientPortalService::class)->feeShare($ticket->load(['enquiry.tickets', 'enquiry.payments']))['balance'];

            if ($balance > 0) {
                ClientPortalService::notify($client, 'payment', 'Payment due: ₹'.number_format($balance), "For {$service}.", route('customer.payments.index'));
            }
        }
    }

    /**
     * Email every active admin (each addressed by name) about something that happened.
     *
     * @param  array<string, string>  $values
     */
    private function mailAdmins(string $templateKey, array $values, ?string $url = null, ?string $linkLabel = null): void
    {
        foreach (Notifier::admins() as $admin) {
            EventMailer::send($templateKey, $admin, $values + ['admin_name' => $admin->name], $url, $linkLabel);
        }
    }

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

        $actor = fn (): string => Auth::guard('web')->user()?->name ?? Auth::guard('customer')->user()?->name ?? 'Someone';

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

            EventMailer::send('employee_document_uploaded', $ticket->assignedTo, [
                'employee_name' => $ticket->assignedTo?->name ?? '',
                'document_name' => $name,
                'service_number' => $ticket->number,
                'service_name' => $ticket->service?->name ?? '',
                'customer_name' => $ticket->customer?->name ?? '',
            ], route('tickets.show', $ticket), 'Review document');
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

                if ($ticket->customer) {
                    ClientPortalService::notify($ticket->customer, 'documents', "{$name} verified", 'Thank you. One more step done.', route('customer.tickets.show', $ticket));
                }

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

            if ($ticket->customer) {
                ClientPortalService::notify($ticket->customer, 'documents', "{$name} needs a re-upload", $document->rejection_reason, route('customer.tickets.show', $ticket));

                EventMailer::send('client_document_rejected', $ticket->customer, [
                    'customer_name' => $ticket->customer->name,
                    'document_name' => $name,
                    'service_name' => $ticket->service?->name ?? '',
                    'reason' => (string) $document->rejection_reason,
                ], route('customer.tickets.show', $ticket), 'Upload a new copy', essential: true);
            }
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

            EventMailer::send('service_reassigned', $assignee, [
                'employee_name' => $assignee->name,
                'service_number' => $ticket->number,
                'service_name' => (string) $service,
            ], $url, 'Open service');

            Notifier::notify(
                array_filter(Notifier::admins(), fn (User $admin) => $admin->id !== $assignee->id),
                'tickets',
                'Service assigned',
                "{$actor()} assigned **{$service}** for {$client} to {$assignee->name}.",
                $url, $ticket->number, 'Open service',
            );

            foreach (array_filter(Notifier::admins(), fn (User $admin) => $admin->id !== $assignee->id) as $admin) {
                EventMailer::send('admin_service_assigned', $admin, [
                    'admin_name' => $admin->name,
                    'actor_name' => $actor(),
                    'service_number' => $ticket->number,
                    'service_name' => (string) $service,
                    'customer_name' => (string) $client,
                    'employee_name' => $assignee->name,
                ], $url, 'Open service');
            }
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
                $this->tellClientAboutProgress($ticket);

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

            if ($enquiry?->customer) {
                $services = $enquiry->tickets->pluck('service.name')->filter()->unique()->values();

                ClientPortalService::notify(
                    $enquiry->customer,
                    'payment',
                    'Payment received: '.$amount,
                    'Thank you. For '.app(ClientPortalService::class)->joinNames($services).'.',
                    route('customer.payments.index'),
                );

                EventMailer::send('payment_received', $enquiry->customer, [
                    'customer_name' => $enquiry->customer->name,
                    'amount' => $amount,
                    'enquiry_number' => $enquiry->number,
                ], route('customer.payments.index'), 'View payments');
            }

            $this->mailAdmins('admin_payment_received', [
                'amount' => $amount,
                'mode' => (string) $payment->mode,
                'customer_name' => (string) $enquiry?->customer?->name,
                'enquiry_number' => (string) $enquiry?->number,
            ], $enquiry ? route('enquiries.show', $enquiry) : null, 'View enquiry');

            Notifier::notify(
                Notifier::admins(),
                'payments',
                'Payment received',
                "**{$amount}** by {$payment->mode} from {$enquiry?->customer?->name}.",
                route('enquiries.show', $enquiry), $enquiry?->number,
            );
        });

        Payment::deleted(function (Payment $payment) use ($actor) {
            if (! Auth::guard('web')->user()?->isEmployee()) {
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

            $this->mailAdmins('admin_payment_deleted', [
                'actor_name' => $actor(),
                'amount' => $amount,
                'mode' => (string) $payment->mode,
                'customer_name' => (string) $enquiry?->customer?->name,
                'enquiry_number' => (string) $enquiry?->number,
            ], $enquiry ? route('enquiries.show', $enquiry) : null, 'View enquiry');
        });

        Customer::created(function (Customer $customer) use ($actor) {
            $creator = Auth::guard('web')->user();

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

            $this->mailAdmins('admin_client_added', [
                'actor_name' => $actor(),
                'customer_name' => $customer->name,
            ], route('customers.show', $customer), 'View client');
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

            $this->mailAdmins('admin_new_enquiry', [
                'actor_name' => $actor(),
                'customer_name' => (string) $enquiry->customer?->name,
                'enquiry_number' => $enquiry->number,
                'total' => $total,
            ], route('enquiries.show', $enquiry), 'View enquiry');
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
