<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmployeeDashboardService
{
    /** Open services with no change for this many days are flagged. */
    private const STALE_DAYS = 7;

    /**
     * Build the employee dashboard from the services assigned to one employee.
     *
     * @return array{
     *     statCards: array<int, array{label: string, count: int, sub: string, gradient: string}>,
     *     attention: array<int, array{tone: string, title: string, detail: string, action: string, url: string}>,
     *     statusBreakdown: array<int, array{label: string, count: int, variant: string}>,
     *     openTotal: int,
     *     workList: Collection<int, array{ticket: Ticket, days: int}>,
     *     followUps: Collection<int, array{ticket: Ticket, days: int}>
     * }
     */
    public function dashboardData(User $employee): array
    {
        $tickets = Ticket::query()
            ->where('assigned_to', $employee->id)
            ->with(['customer', 'service', 'documents'])
            ->latest('created_at')
            ->get();

        $open = $tickets->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed())->values();

        $waitingOnClient = [
            TicketStatus::DocumentsPending,
            TicketStatus::PartiallyReceived,
            TicketStatus::AdditionalDocumentsRequired,
        ];
        $waiting = $open->filter(fn (Ticket $ticket): bool => in_array($ticket->status, $waitingOnClient, true))->values();

        $toCheck = $tickets->flatMap(
            fn (Ticket $ticket) => $ticket->documents->where('status', 'Awaiting check')->map(fn ($document) => ['ticket' => $ticket, 'document' => $document])
        )->values();

        $completedThisMonth = $tickets->filter(fn (Ticket $ticket): bool => $ticket->status?->isClosed() && $ticket->completed_at?->isCurrentMonth())->count();
        $completedLastMonth = $tickets->filter(fn (Ticket $ticket): bool => $ticket->status?->isClosed() && $ticket->completed_at?->isSameMonth(now()->subMonthNoOverflow()))->count();
        $change = $completedThisMonth - $completedLastMonth;

        $statCards = [
            ['label' => 'My open services', 'count' => $open->count(), 'sub' => 'assigned to you', 'gradient' => 'blue', 'url' => route('tickets.index')],
            ['label' => 'Documents to check', 'count' => $toCheck->count(), 'sub' => 'uploaded by clients', 'gradient' => 'purple'],
            ['label' => 'Waiting for clients', 'count' => $waiting->count(), 'sub' => 'documents not received', 'gradient' => 'amber'],
            [
                'label' => 'Completed ('.now()->format('M').')',
                'count' => $completedThisMonth,
                'sub' => ($change >= 0 ? '+' : '−').abs($change).' vs '.now()->subMonthNoOverflow()->format('F'),
                'gradient' => 'green',
                'url' => route('tickets.index', ['tab' => 'completed']),
            ],
        ];

        return [
            'statCards' => $statCards,
            'attention' => $this->attention($open, $toCheck),
            'statusBreakdown' => $this->statusBreakdown($open),
            'openTotal' => $open->count(),
            'workList' => $open->sortBy('created_at')->take(5)->values()
                ->map(fn (Ticket $ticket): array => ['ticket' => $ticket, 'days' => $this->daysSince($ticket->created_at)]),
            'followUps' => $waiting->sortBy('updated_at')->take(4)->values()
                ->map(fn (Ticket $ticket): array => ['ticket' => $ticket, 'days' => $this->daysSince($ticket->updated_at)]),
        ];
    }

    /**
     * What needs the employee's attention today, most pressing first.
     *
     * @return array<int, array{tone: string, title: string, detail: string, action: string, url: string}>
     */
    private function attention(Collection $open, Collection $toCheck): array
    {
        $items = [];

        if ($toCheck->isNotEmpty()) {
            $count = $toCheck->count();
            $items[] = [
                'tone' => 'purple',
                'title' => $count.' '.str('document')->plural($count).' waiting for your check',
                'detail' => $this->clientNames($toCheck->pluck('ticket')),
                'action' => 'Check now',
                'url' => route('tickets.show', $toCheck->first()['ticket']),
            ];
        }

        $stale = $open
            ->reject(fn (Ticket $ticket): bool => $ticket->status === TicketStatus::OnHold)
            ->filter(fn (Ticket $ticket): bool => $this->daysSince($ticket->updated_at) >= self::STALE_DAYS)
            ->sortBy('updated_at')->values();

        if ($stale->isNotEmpty()) {
            $count = $stale->count();
            $items[] = [
                'tone' => 'amber',
                'title' => $count.' '.str('service')->plural($count).' with no update for '.self::STALE_DAYS.' days',
                'detail' => $stale->pluck('number')->take(3)->implode(', '),
                'action' => 'Update status',
                'url' => route('tickets.show', $stale->first()),
            ];
        }

        $onHold = $open->filter(fn (Ticket $ticket): bool => $ticket->status === TicketStatus::OnHold)->sortBy('updated_at')->values();

        if ($onHold->isNotEmpty()) {
            $count = $onHold->count();
            $first = $onHold->first();
            $items[] = [
                'tone' => 'red',
                'title' => $count.' '.str('service')->plural($count).' on hold'.($count === 1 ? ' for '.$this->daysSince($first->updated_at).' days' : ''),
                'detail' => $count === 1
                    ? $first->number.' · '.($first->customer?->name ?? '—')
                    : $onHold->pluck('number')->take(3)->implode(', '),
                'action' => 'Open service',
                'url' => route('tickets.show', $first),
            ];
        }

        $ready = $open->filter(fn (Ticket $ticket): bool => $ticket->status === TicketStatus::SubmittedToDepartment)->values();

        if ($ready->isNotEmpty()) {
            $count = $ready->count();
            $first = $ready->first();
            $items[] = [
                'tone' => 'green',
                'title' => $count.' '.str('service')->plural($count).' ready to mark completed',
                'detail' => $first->number.' · '.($first->service?->name ?? '—').($count > 1 ? ' and '.($count - 1).' more' : ''),
                'action' => 'Mark completed',
                'url' => route('tickets.show', $first),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{label: string, count: int, slug: string}>
     */
    private function statusBreakdown(Collection $open): array
    {
        return collect(TicketStatus::cases())
            ->reject(fn (TicketStatus $status): bool => $status->isClosed())
            ->map(fn (TicketStatus $status): array => [
                'label' => $status->value,
                'count' => $open->where('status', $status)->count(),
                'slug' => Str::slug($status->value),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values()
            ->all();
    }

    private function clientNames(Collection $tickets): string
    {
        $names = $tickets->map(fn (Ticket $ticket) => $ticket->customer?->name)->filter()->unique()->values();

        return $names->take(3)->implode(', ').($names->count() > 3 ? ' and '.($names->count() - 3).' more' : '');
    }

    private function daysSince(?\DateTimeInterface $date): int
    {
        return $date ? (int) abs(Carbon::instance($date)->diffInDays(now())) : 0;
    }
}
