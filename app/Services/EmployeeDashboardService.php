<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeeDashboardService
{
    /**
     * Build dashboard metrics and chart series from tickets assigned to one employee.
     *
     * @return array{
     *     myTickets: Collection<int, Ticket>,
     *     statCards: array<int, array{label: string, count: int, prefix: string, trend: string, trendColor: string, gradient: string}>,
     *     months: array<int, string>,
     *     newTickets: array<int, int>,
     *     completedTickets: array<int, int>,
     *     statusBreakdown: array<int, array{label: string, count: int, color: string}>,
     *     openTicketsTotal: int,
     *     feesReceived: array<int, float>,
     *     ticketsByService: Collection<int, array{name: string, open: int, completed: int}>
     * }
     */
    public function dashboardData(User $employee): array
    {
        $tickets = Ticket::query()
            ->where('assigned_to', $employee->id)
            ->with(['customer', 'service', 'enquiry.payments', 'documents.serviceDocument'])
            ->latest('created_at')
            ->get();

        $openTickets = $tickets->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed());
        $clientWaitingStatuses = [
            TicketStatus::DocumentsPending,
            TicketStatus::PartiallyReceived,
            TicketStatus::AdditionalDocumentsRequired,
        ];
        $awaitingActionTickets = $openTickets->reject(
            fn (Ticket $ticket): bool => in_array($ticket->status, $clientWaitingStatuses, true)
        )->values();
        $completedThisMonthTickets = $tickets->filter(
            fn (Ticket $ticket): bool => $ticket->status?->isClosed()
                && $ticket->completed_at?->isCurrentMonth()
        )->values();
        $documentsToVerifyList = $tickets->flatMap(
            fn (Ticket $ticket) => $ticket->documents
                ->where('status', 'Awaiting check')
                ->map(fn ($document) => [
                    'ticket' => $ticket,
                    'document' => $document,
                ])
        )->values();
        $documentsToVerify = $documentsToVerifyList->count();

        $statCards = [
            [
                'label' => 'My open tickets',
                'count' => $openTickets->count(),
                'prefix' => '',
                'trend' => 'Currently assigned to you',
                'trendColor' => 'rgba(255,255,255,.75)',
                'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)',
            ],
            [
                'label' => 'Awaiting my action',
                'count' => $awaitingActionTickets->count(),
                'prefix' => '',
                'trend' => 'Excludes tickets waiting for clients',
                'trendColor' => '#ffffff',
                'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)',
            ],
            [
                'label' => 'Completed this month',
                'count' => $completedThisMonthTickets->count(),
                'prefix' => '',
                'trend' => 'Task completed or paid',
                'trendColor' => '#ffffff',
                'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)',
            ],
            [
                'label' => 'Documents to verify',
                'count' => $documentsToVerify,
                'prefix' => '',
                'trend' => 'On your assigned tickets',
                'trendColor' => '#ffffff',
                'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)',
            ],
        ];

        $months = collect(range(0, 5))->map(
            fn (int $offset): string => now()->startOfMonth()->subMonths(5 - $offset)->format('M')
        )->all();
        $monthKeys = collect(range(0, 5))->map(
            fn (int $offset): string => now()->startOfMonth()->subMonths(5 - $offset)->format('Y-m')
        );

        $newTickets = $monthKeys->map(
            fn (string $month): int => $tickets->filter(
                fn (Ticket $ticket): bool => $ticket->created_at?->format('Y-m') === $month
            )->count()
        )->all();
        $completedTickets = $monthKeys->map(
            fn (string $month): int => $tickets->filter(
                fn (Ticket $ticket): bool => $ticket->status?->isClosed()
                    && $ticket->completed_at?->format('Y-m') === $month
            )->count()
        )->all();
        // Payments belong to the enquiry, not the ticket (an enquiry's tickets
        // can share a discount), so fees received are pulled from Payment rows
        // directly rather than summed per ticket — avoids double-counting a
        // payment when more than one of its enquiry's tickets is assigned here.
        $myPayments = Payment::query()
            ->whereHas('enquiry.tickets', fn ($query) => $query->where('assigned_to', $employee->id))
            ->get();

        $feesReceived = $monthKeys->map(
            fn (string $month): float => (float) $myPayments
                ->filter(fn (Payment $payment): bool => $payment->paid_at?->format('Y-m') === $month)
                ->sum('amount')
        )->all();

        $statusColors = [
            TicketStatus::DocumentsPending->value => '#9aa1b0',
            TicketStatus::PartiallyReceived->value => '#6b7280',
            TicketStatus::DocumentsReceived->value => '#3b82f6',
            TicketStatus::UnderVerification->value => '#6d5bd0',
            TicketStatus::AdditionalDocumentsRequired->value => '#f97316',
            TicketStatus::WorkInProgress->value => '#4a9b3e',
            TicketStatus::SubmittedToDepartment->value => '#c026d3',
            TicketStatus::OnHold->value => '#dc3545',
            TicketStatus::TaskCompleted->value => '#1f6b30',
        ];
        $statusBreakdown = collect(TicketStatus::cases())
            ->map(fn (TicketStatus $status): array => [
                'label' => $status->value,
                'count' => $tickets->filter(fn (Ticket $ticket): bool => $ticket->status === $status && ! $status->isClosed())->count(),
                'color' => $statusColors[$status->value],
            ])
            ->filter(fn (array $status): bool => $status['count'] > 0)
            ->values()
            ->all();

        $ticketsByService = $tickets->groupBy(
            fn (Ticket $ticket): string => (string) $ticket->service_id
        )->map(function (Collection $serviceTickets): array {
            return [
                'name' => $serviceTickets->first()->service?->name ?? 'Service',
                'open' => $serviceTickets->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed())->count(),
                'completed' => $serviceTickets->filter(fn (Ticket $ticket): bool => $ticket->status?->isClosed())->count(),
            ];
        })->sortBy('name')->values();

        return [
            'myTickets' => $tickets->take(5)->values(),
            'statCards' => $statCards,
            'openTicketsList' => $openTickets->values(),
            'awaitingActionList' => $awaitingActionTickets,
            'completedThisMonthList' => $completedThisMonthTickets,
            'documentsToVerifyList' => $documentsToVerifyList,
            'months' => $months,
            'newTickets' => $newTickets,
            'completedTickets' => $completedTickets,
            'statusBreakdown' => $statusBreakdown,
            'openTicketsTotal' => $openTickets->count(),
            'feesReceived' => $feesReceived,
            'ticketsByService' => $ticketsByService,
        ];
    }
}
