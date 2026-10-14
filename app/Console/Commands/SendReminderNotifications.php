<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\AppNotification;
use App\Models\Enquiry;
use App\Models\Ticket;
use App\Services\NotificationService as Notifier;
use Illuminate\Console\Command;

class SendReminderNotifications extends Command
{
    /** A service still open after this many days gets a reminder... */
    public const OPEN_SERVICE_AFTER_DAYS = 15;

    /** ...and a completed service with fees still unpaid after this many days. */
    public const UNPAID_AFTER_DAYS = 7;

    /** After the first reminder, repeat on this interval until it is resolved. */
    public const REPEAT_EVERY_DAYS = 7;

    protected $signature = 'notifications:send-reminders';

    protected $description = 'Remind admins about old open services and unpaid fees on completed services';

    public function handle(): int
    {
        $open = $this->remindAboutOpenServices();
        $unpaid = $this->remindAboutUnpaidFees();

        $this->info("Open-service reminders: {$open}. Unpaid-fee reminders: {$unpaid}.");

        return self::SUCCESS;
    }

    private function dueToday(int $days, int $firstAfter): bool
    {
        return $days >= $firstAfter && ($days - $firstAfter) % self::REPEAT_EVERY_DAYS === 0;
    }

    /**
     * Skip a reminder that was already sent today (e.g. if the command runs twice).
     */
    private function alreadySentToday(string $title, ?string $reference): bool
    {
        return AppNotification::query()
            ->whereDate('created_at', today())
            ->where('title', $title)
            ->where('link_text', $reference)
            ->exists();
    }

    private function remindAboutOpenServices(): int
    {
        $sent = 0;

        Ticket::query()
            ->with(['assignedTo', 'service', 'customer'])
            ->where('status', '!=', TicketStatus::TaskCompleted->value)
            ->where('created_at', '<=', now()->subDays(self::OPEN_SERVICE_AFTER_DAYS)->endOfDay())
            ->each(function (Ticket $ticket) use (&$sent) {
                $days = (int) $ticket->created_at->startOfDay()->diffInDays(now()->startOfDay());

                if (! $this->dueToday($days, self::OPEN_SERVICE_AFTER_DAYS)) {
                    return;
                }

                $title = "Service open for {$days} days";

                if ($this->alreadySentToday($title, $ticket->number)) {
                    return;
                }

                Notifier::notify(
                    [...Notifier::admins(), $ticket->assignedTo],
                    'tickets',
                    $title,
                    "**{$ticket->service?->name}** for {$ticket->customer?->name} is still {$ticket->status->value}.",
                    route('tickets.show', $ticket), $ticket->number, 'Open service',
                );

                $sent++;
            });

        return $sent;
    }

    private function remindAboutUnpaidFees(): int
    {
        $sent = 0;

        Enquiry::query()
            ->with(['customer', 'payments'])
            ->whereHas('tickets', fn ($query) => $query->where('status', TicketStatus::TaskCompleted->value))
            ->each(function (Enquiry $enquiry) use (&$sent) {
                if ($enquiry->balanceDue() <= 0) {
                    return;
                }

                $completedAt = $enquiry->tickets()
                    ->where('status', TicketStatus::TaskCompleted->value)
                    ->whereNotNull('completed_at')
                    ->min('completed_at');

                if (! $completedAt) {
                    return;
                }

                $days = (int) \Illuminate\Support\Carbon::parse($completedAt)->startOfDay()->diffInDays(now()->startOfDay());

                if (! $this->dueToday($days, self::UNPAID_AFTER_DAYS)) {
                    return;
                }

                $title = "Fee pending for {$days} days";

                if ($this->alreadySentToday($title, $enquiry->number)) {
                    return;
                }

                $balance = '₹'.number_format($enquiry->balanceDue(), 2);

                Notifier::notify(
                    Notifier::admins(),
                    'payments',
                    $title,
                    "{$enquiry->customer?->name} still owes **{$balance}** on a completed service.",
                    route('enquiries.show', $enquiry), $enquiry->number, 'Record payment',
                );

                $sent++;
            });

        return $sent;
    }
}
