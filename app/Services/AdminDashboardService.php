<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminDashboardService
{
    /** Open services older than this many days are flagged. */
    private const OLD_DAYS = 30;

    /** Clients who have not sent documents for this many days are flagged. */
    private const CLIENT_WAIT_DAYS = 15;

    public function __construct(private readonly ClientPortalService $portal) {}

    /**
     * Everything the practice overview shows, from live data.
     *
     * @return array<string, mixed>
     */
    public function dashboardData(): array
    {
        $tickets = Ticket::query()
            ->with(['customer', 'service.documents', 'documents', 'assignedTo', 'enquiry.payments', 'enquiry.tickets'])
            ->get();

        $open = $tickets->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed())->values();

        return [
            'statCards' => $this->statCards($open),
            'attention' => $this->attention($tickets, $open),
            'statusBreakdown' => $this->statusBreakdown($open),
            'openTotal' => $open->count(),
            'recentEnquiries' => Enquiry::query()->with(['customer', 'createdBy', 'tickets'])->latest('created_at')->latest('id')->limit(5)->get(),
            'staffWorkload' => $this->staffWorkload($tickets),
            'recentPayments' => Payment::query()->with('enquiry.customer')->latest('paid_at')->latest('id')->limit(5)->get(),
            'financialYear' => $this->financialYear(),
        ];
    }

    private function financialYear(): string
    {
        $start = now()->month >= 4 ? now()->year : now()->year - 1;

        return 'FY '.$start.'-'.substr((string) ($start + 1), -2);
    }

    /**
     * @return array<int, array{label: string, count: int, prefix: string, sub: string, gradient: string, url: string}>
     */
    private function statCards(Collection $open): array
    {
        $monthStart = now()->startOfMonth();
        $lastStart = now()->subMonthNoOverflow()->startOfMonth();
        $monthName = now()->format('M');
        $lastName = $lastStart->format('M');

        $count = fn ($query, string $column, Carbon $from, ?Carbon $to = null) => (clone $query)
            ->where($column, '>=', $from)
            ->when($to, fn ($q) => $q->where($column, '<', $to))
            ->count();

        $enquiriesNow = $count(Enquiry::query(), 'created_at', $monthStart);
        $enquiriesBefore = $count(Enquiry::query(), 'created_at', $lastStart, $monthStart);

        $completedBase = Ticket::query()->whereNotNull('completed_at');
        $completedNow = $count($completedBase, 'completed_at', $monthStart);
        $completedBefore = $count($completedBase, 'completed_at', $lastStart, $monthStart);

        $receivedNow = (float) Payment::query()->where('paid_at', '>=', $monthStart)->sum('amount');
        $receivedBefore = (float) Payment::query()->where('paid_at', '>=', $lastStart)->where('paid_at', '<', $monthStart)->sum('amount');

        $owing = Enquiry::query()->with(['payments', 'tickets'])->get()->filter(fn (Enquiry $enquiry): bool => $enquiry->balanceDue() > 0);

        $openedThisWeek = Ticket::query()->where('created_at', '>=', now()->subDays(7))->count();

        return [
            ['label' => 'New enquiries ('.$monthName.')', 'count' => $enquiriesNow, 'prefix' => '', 'sub' => $this->change($enquiriesNow, $enquiriesBefore, $lastName), 'gradient' => 'blue', 'url' => route('enquiries.index')],
            ['label' => 'Open services', 'count' => $open->count(), 'prefix' => '', 'sub' => $openedThisWeek.' new in the last 7 days', 'gradient' => 'purple', 'url' => route('tickets.index')],
            ['label' => 'Completed ('.$monthName.')', 'count' => $completedNow, 'prefix' => '', 'sub' => $this->change($completedNow, $completedBefore, $lastName), 'gradient' => 'green', 'url' => route('tickets.index', ['tab' => 'completed'])],
            ['label' => 'Fees pending', 'count' => (int) round($owing->sum(fn (Enquiry $enquiry): float => $enquiry->balanceDue())), 'prefix' => '₹', 'sub' => 'across '.$owing->pluck('customer_id')->unique()->count().' '.Str::plural('client', $owing->pluck('customer_id')->unique()->count()), 'gradient' => 'maroon', 'url' => route('payments.index')],
            ['label' => 'Fees received ('.$monthName.')', 'count' => (int) round($receivedNow), 'prefix' => '₹', 'sub' => $this->change($receivedNow, $receivedBefore, $lastName), 'gradient' => 'teal', 'url' => route('payments.index')],
        ];
    }

    private function change(float|int $now, float|int $before, string $beforeName): string
    {
        if ($before <= 0) {
            return $now > 0 ? 'none in '.$beforeName : 'same as '.$beforeName;
        }

        $percent = (int) round(($now - $before) / $before * 100);

        return ($percent >= 0 ? '+' : '−').abs($percent).'% vs '.$beforeName;
    }

    /**
     * @return array<int, array{tone: string, title: string, detail: string, action: string, url: string}>
     */
    private function attention(Collection $tickets, Collection $open): array
    {
        $items = [];

        $unpaid = $tickets->filter(fn (Ticket $ticket): bool => $ticket->status === TicketStatus::TaskCompleted && $ticket->enquiry && $ticket->enquiry->balanceDue() > 0);
        if ($unpaid->isNotEmpty()) {
            $count = $unpaid->count();
            $items[] = [
                'tone' => 'red',
                'title' => $count.' '.Str::plural('service', $count).' ready for final payment',
                'detail' => 'Task completed, fees not recorded yet',
                'action' => 'Record payments',
                'url' => route('payments.index'),
            ];
        }

        $waiting = $open->filter(fn (Ticket $ticket): bool => in_array($ticket->status, [
            TicketStatus::DocumentsPending, TicketStatus::PartiallyReceived, TicketStatus::AdditionalDocumentsRequired,
        ], true));
        $missing = $waiting->sum(fn (Ticket $ticket): int => $this->portal->documentRows($ticket)
            ->filter(fn (array $row): bool => $row['needsUpload'] && $row['serviceDocument']->is_mandatory)->count());
        if ($missing > 0) {
            $longWaiting = $waiting->filter(fn (Ticket $ticket): bool => $ticket->updated_at && $ticket->updated_at->lte(now()->subDays(self::CLIENT_WAIT_DAYS)))
                ->pluck('customer_id')->unique()->count();
            $items[] = [
                'tone' => 'amber',
                'title' => $missing.' '.Str::plural('document', $missing).' waiting from clients',
                'detail' => $longWaiting > 0
                    ? $longWaiting.' '.Str::plural('client', $longWaiting).' waiting more than '.self::CLIENT_WAIT_DAYS.' days'
                    : 'No client waiting more than '.self::CLIENT_WAIT_DAYS.' days',
                'action' => 'View list',
                'url' => route('tickets.index'),
            ];
        }

        $old = $open->filter(fn (Ticket $ticket): bool => $ticket->created_at->lte(now()->subDays(self::OLD_DAYS)))->sortBy('created_at')->values();
        if ($old->isNotEmpty()) {
            $count = $old->count();
            $oldest = $old->first();
            $items[] = [
                'tone' => 'red',
                'title' => $count.' '.Str::plural('service', $count).' open more than '.self::OLD_DAYS.' days',
                'detail' => 'Oldest: '.$oldest->number.' · '.(int) abs($oldest->created_at->diffInDays(now())).' days',
                'action' => 'View services',
                'url' => route('tickets.index'),
            ];
        }

        $unassigned = $open->filter(fn (Ticket $ticket): bool => $ticket->assigned_to === null)->values();
        if ($unassigned->isNotEmpty()) {
            $count = $unassigned->count();
            $first = $unassigned->first();
            $items[] = [
                'tone' => 'blue',
                'title' => $count.' '.Str::plural('service', $count).' not assigned',
                'detail' => $first->number.' · '.($first->service?->name ?? '—').($count > 1 ? ' and '.($count - 1).' more' : ''),
                'action' => 'Assign',
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

    /**
     * @return Collection<int, array{name: string, open: int, completed: int}>
     */
    private function staffWorkload(Collection $tickets): Collection
    {
        $fyStart = Carbon::create(now()->month >= 4 ? now()->year : now()->year - 1, 4, 1)->startOfDay();

        return User::query()
            ->where('role', UserRole::Employee)
            ->where('is_active', true)
            ->get()
            ->map(function (User $employee) use ($tickets, $fyStart): array {
                $mine = $tickets->where('assigned_to', $employee->id);

                return [
                    'name' => $employee->name,
                    'open' => $mine->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed())->count(),
                    'completed' => $mine->filter(fn (Ticket $ticket): bool => $ticket->completed_at && $ticket->completed_at->gte($fyStart))->count(),
                ];
            })
            ->sortByDesc('open')
            ->take(5)
            ->values();
    }
}
