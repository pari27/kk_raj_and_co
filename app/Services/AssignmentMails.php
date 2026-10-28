<?php

namespace App\Services;

use App\Models\Enquiry;
use App\Models\Ticket;

/**
 * Gathers the "service assigned" emails while an enquiry is being saved, so that an employee who is
 * given several services in the same enquiry gets ONE email listing them, instead of one per service.
 * Admins likewise get one email for the whole enquiry. Nothing is gathered outside an enquiry, so a
 * single service assigned or reassigned on its own is still emailed straight away.
 */
class AssignmentMails
{
    /** @var array<int, array{ticket: Ticket, actor: string}>|null */
    private static ?array $pending = null;

    /** @var array{enquiry: Enquiry, actor: string, total: string}|null */
    private static ?array $heldEnquiry = null;

    public static function collect(): void
    {
        self::$pending = [];
        self::$heldEnquiry = null;

        // If saving the enquiry fails, nothing may stay switched on for the next request.
        app()->terminating(fn () => self::discard());
    }

    public static function isCollecting(): bool
    {
        return self::$pending !== null;
    }

    public static function add(Ticket $ticket, string $actor): void
    {
        self::$pending[] = ['ticket' => $ticket, 'actor' => $actor];
    }

    /**
     * Forget what was gathered (for example when saving the enquiry failed).
     */
    public static function discard(): void
    {
        self::$pending = null;
        self::$heldEnquiry = null;
    }

    /**
     * Keep the "new enquiry" email for the admins, to be sent together with the services (see flush()).
     */
    public static function holdNewEnquiry(Enquiry $enquiry, string $actor, string $total): void
    {
        self::$heldEnquiry = ['enquiry' => $enquiry, 'actor' => $actor, 'total' => $total];
    }

    /**
     * Send what was gathered, as one email per person.
     *
     * @param  iterable<int, Ticket>  $createdTickets  Every service the enquiry created, assigned or not
     */
    public static function flush(iterable $createdTickets = []): void
    {
        $items = self::$pending ?? [];
        $held = self::$heldEnquiry;
        self::$pending = null;
        self::$heldEnquiry = null;

        // 1. Each assignee: one email for all the services they were given.
        foreach (collect($items)->groupBy(fn (array $item) => $item['ticket']->assigned_to) as $group) {
            $assignee = $group->first()['ticket']->assignedTo;

            if (! $assignee) {
                continue;
            }

            if ($group->count() === 1) {
                $ticket = $group->first()['ticket'];

                EventMailer::send('service_reassigned', $assignee, [
                    'employee_name' => $assignee->name,
                    'service_number' => $ticket->number,
                    'service_name' => (string) $ticket->service?->name,
                ], route('tickets.show', $ticket), 'Open service');

                continue;
            }

            EventMailer::send('services_assigned', $assignee, [
                'employee_name' => $assignee->name,
                'services_list' => $group->map(fn (array $item) => self::line($item['ticket'], false))->implode("\n"),
            ], route('tickets.index'), 'Open my services');
        }

        // 2. Each admin: ONE email about the new enquiry, with every service and who it went to.
        if ($held) {
            $enquiry = $held['enquiry'];
            $list = collect($createdTickets)->map(fn (Ticket $ticket) => self::line($ticket, true))->implode("\n");

            foreach (NotificationService::admins() as $admin) {
                EventMailer::send('admin_new_enquiry', $admin, [
                    'admin_name' => $admin->name,
                    'actor_name' => $held['actor'],
                    'customer_name' => (string) $enquiry->customer?->name,
                    'enquiry_number' => $enquiry->number,
                    'total' => $held['total'],
                    'services_list' => $list !== '' ? $list : '-',
                ], route('enquiries.show', $enquiry), 'View enquiry');
            }
        }
    }
    private static function line(Ticket $ticket, bool $withAssignee): string
    {
        $text = '- '.$ticket->number.': '.($ticket->service?->name ?? 'Service');

        if ($ticket->customer) {
            $text .= ' (for '.$ticket->customer->name.')';
        }

        if (! $withAssignee) {
            return $text;
        }

        return $text.' → '.($ticket->assignedTo?->name ?? 'Unassigned');
    }
}
