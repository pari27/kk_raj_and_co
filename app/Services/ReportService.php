<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\EmployeeDesignation;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds every report as the same simple table structure, so one view and one
 * CSV export can render any of them.
 *
 * Column types: text, strong, link ({text,url}), money, money_green, money_red,
 * days ({value}, red from `danger_at`), status (a TicketStatus value),
 * chip ({text,tone}).
 */
class ReportService
{
    public const GROUPS = [
        'Money' => ['fees_collected', 'fees_pending', 'service_summary', 'month_summary'],
        'Work' => ['services_by_status', 'staff_work', 'pending_documents', 'enquiries'],
        'Clients' => ['client_history'],
    ];

    public const REPORTS = [
        'fees_collected' => ['label' => 'Fees collected', 'description' => 'Payments received in the period', 'filters' => ['from', 'to', 'service_id', 'staff_id', 'mode']],
        'fees_pending' => ['label' => 'Fees pending', 'description' => 'Unpaid fees, oldest first', 'filters' => ['as_on', 'service_id', 'staff_id']],
        'service_summary' => ['label' => 'Service-wise summary', 'description' => 'Billed, received and pending per service', 'filters' => ['from', 'to', 'service_id']],
        'month_summary' => ['label' => 'Month-wise summary', 'description' => 'Billed, received and pending per month', 'filters' => ['fy']],
        'services_by_status' => ['label' => 'Services by status', 'description' => 'Every service and where it stands', 'filters' => ['from', 'to', 'status', 'service_id', 'staff_id']],
        'staff_work' => ['label' => 'Staff work', 'description' => 'Assigned, completed and open per person', 'filters' => ['from', 'to', 'staff_id', 'designation_id']],
        'pending_documents' => ['label' => 'Pending documents', 'description' => 'Documents clients still need to upload', 'filters' => ['from', 'to', 'service_id', 'staff_id']],
        'enquiries' => ['label' => 'Enquiries', 'description' => 'Enquiries received and their totals', 'filters' => ['from', 'to', 'created_by', 'enquiry_status']],
        'client_history' => ['label' => 'Client history', 'description' => 'All services for one client', 'filters' => ['client_id', 'from', 'to']],
    ];

    /** Reports whose rows are long lists (paged); the rest are short summaries. */
    public const PAGED = ['fees_collected', 'fees_pending', 'services_by_status', 'pending_documents', 'enquiries'];

    public const MODES = ['UPI', 'Bank transfer', 'Cash', 'Cheque'];

    /**
     * Default filter values for a report.
     *
     * @return array<string, string>
     */
    public function defaults(string $report): array
    {
        $today = now();
        $fyStart = Carbon::create($today->month >= 4 ? $today->year : $today->year - 1, 4, 1);

        return match ($report) {
            'fees_pending' => ['as_on' => $today->toDateString()],
            'month_summary' => ['fy' => (string) $fyStart->year],
            'service_summary', 'staff_work', 'client_history' => ['from' => $fyStart->toDateString(), 'to' => $today->toDateString()],
            default => ['from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString()],
        };
    }

    /**
     * Options for the filter drop-downs, limited to what the user may see.
     *
     * @return array<string, mixed>
     */
    public function options(User $user): array
    {
        $staff = User::query()
            ->where('role', UserRole::Employee->value)
            ->when($user->isEmployee(), fn (Builder $query) => $query->whereKey($user->id))
            ->orderBy('name')->get(['id', 'name']);

        $clients = Customer::query()
            ->when($user->isEmployee(), fn (Builder $query) => $query->whereHas('tickets', fn (Builder $tickets) => $tickets->visibleTo($user)))
            ->orderBy('name')->get(['id', 'name', 'phone']);

        return [
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'staff' => $staff,
            'clients' => $clients,
            'designations' => EmployeeDesignation::query()->orderBy('name')->get(['id', 'name']),
            'creators' => User::query()
                ->whereIn('role', [UserRole::SuperAdmin->value, UserRole::Admin->value, UserRole::Employee->value])
                ->when($user->isEmployee(), fn (Builder $query) => $query->whereKey($user->id))
                ->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn (TicketStatus $status) => $status->value, TicketStatus::cases()),
            'modes' => self::MODES,
            'years' => $this->financialYears(),
        ];
    }

    /** @return array<int, string> start year => label */
    private function financialYears(): array
    {
        $current = now()->month >= 4 ? now()->year : now()->year - 1;
        $years = [];

        for ($year = $current; $year >= $current - 3; $year--) {
            $years[$year] = 'FY '.$year.'-'.substr((string) ($year + 1), -2).' (Apr – Mar)';
        }

        return $years;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, subtitle: string, columns: array<int, array<string, mixed>>, rows: Collection<int, array<string, mixed>>, total: array<string, mixed>|null, total_label: string, client: Customer|null, note: string|null}
     */
    public function run(string $report, array $filters, User $user): array
    {
        $result = match ($report) {
            'fees_pending' => $this->feesPending($filters, $user),
            'service_summary' => $this->serviceSummary($filters, $user),
            'month_summary' => $this->monthSummary($filters, $user),
            'services_by_status' => $this->servicesByStatus($filters, $user),
            'staff_work' => $this->staffWork($filters, $user),
            'pending_documents' => $this->pendingDocuments($filters, $user),
            'enquiries' => $this->enquiries($filters, $user),
            'client_history' => $this->clientHistory($filters, $user),
            default => $this->feesCollected($filters, $user),
        };

        return $result + ['title' => self::REPORTS[$report]['label'] ?? 'Fees collected', 'client' => null, 'note' => null];
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    private function range(array $filters): string
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : null;

        return match (true) {
            $from && $to => $from->format('j M').' – '.$to->format('j M Y'),
            (bool) $from => 'From '.$from->format('j M Y'),
            (bool) $to => 'Up to '.$to->format('j M Y'),
            default => 'All time',
        };
    }

    private function ticketQuery(array $filters, User $user, bool $withDates = true): Builder
    {
        return Ticket::query()
            ->visibleTo($user)
            ->when($withDates && ! empty($filters['from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($withDates && ! empty($filters['to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['service_id']), fn (Builder $query) => $query->where('service_id', $filters['service_id']))
            ->when(! empty($filters['staff_id']), fn (Builder $query) => $query->where('assigned_to', $filters['staff_id']))
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']));
    }

    /**
     * Payment is tracked per enquiry, so a service's share of an enquiry's
     * billed and received amounts follows its share of the enquiry's services.
     * Needs the ticket's enquiry with all its tickets and payments loaded.
     *
     * @return array{billed: float, paid: float, balance: float}
     */
    private function share(Ticket $ticket, ?Carbon $paidUpTo = null): array
    {
        $enquiry = $ticket->enquiry;

        if (! $enquiry) {
            return ['billed' => (float) $ticket->total, 'paid' => 0.0, 'balance' => (float) $ticket->total];
        }

        $sum = (float) $enquiry->tickets->sum('total');
        $ratio = $sum > 0 ? (float) $ticket->total / $sum : 0.0;

        $payments = $paidUpTo
            ? $enquiry->payments->filter(fn (Payment $payment) => $payment->paid_at && $payment->paid_at->lte($paidUpTo))
            : $enquiry->payments;

        $billed = (float) $enquiry->total * $ratio;
        $paid = (float) $payments->sum('amount') * $ratio;

        return ['billed' => $billed, 'paid' => $paid, 'balance' => max(0.0, $billed - $paid)];
    }

    private function money(float $amount): string
    {
        return '₹'.number_format($amount);
    }

    // ------------------------------------------------------------------
    // Money
    // ------------------------------------------------------------------

    private function feesCollected(array $filters, User $user): array
    {
        $payments = Payment::query()
            ->with(['enquiry.customer', 'enquiry.tickets.service', 'receivedBy'])
            ->whereHas('enquiry', fn (Builder $enquiry) => $enquiry->visibleTo($user))
            ->when(! empty($filters['from']), fn (Builder $query) => $query->whereDate('paid_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn (Builder $query) => $query->whereDate('paid_at', '<=', $filters['to']))
            ->when(! empty($filters['mode']), fn (Builder $query) => $query->where('mode', $filters['mode']))
            ->when(! empty($filters['service_id']), fn (Builder $query) => $query->whereHas('enquiry.tickets', fn (Builder $tickets) => $tickets->where('service_id', $filters['service_id'])))
            ->when(! empty($filters['staff_id']), fn (Builder $query) => $query->whereHas('enquiry.tickets', fn (Builder $tickets) => $tickets->where('assigned_to', $filters['staff_id'])))
            ->latest('paid_at')->latest('id')
            ->get();

        $rows = $payments->map(fn (Payment $payment) => [
            'date' => $payment->paid_at?->format('j M Y'),
            'enquiry' => ['text' => $payment->enquiry?->number ?? '—', 'url' => $payment->enquiry ? route('enquiries.show', $payment->enquiry) : null],
            'client' => $payment->enquiry?->customer?->name ?? '—',
            'services' => $payment->enquiry?->tickets->filter(fn (Ticket $ticket) => ! $user->isEmployee() || $ticket->assigned_to === $user->id)->pluck('service.name')->filter()->implode(', ') ?: '—',
            'mode' => $payment->mode,
            'reference' => $payment->reference ?: '—',
            'received_by' => $payment->receivedBy?->name ?? '—',
            'amount' => (float) $payment->amount,
        ]);

        return [
            'subtitle' => $this->range($filters).' · '.($this->serviceName($filters) ?? 'All services').' · '.($this->staffName($filters) ?? 'All staff').' · '.($filters['mode'] ?? 'All modes'),
            'columns' => [
                ['key' => 'date', 'label' => 'Date', 'type' => 'text'],
                ['key' => 'enquiry', 'label' => 'Enquiry', 'type' => 'link'],
                ['key' => 'client', 'label' => 'Client', 'type' => 'strong'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'text'],
                ['key' => 'mode', 'label' => 'Mode', 'type' => 'text'],
                ['key' => 'reference', 'label' => 'Reference', 'type' => 'text'],
                ['key' => 'received_by', 'label' => 'Received by', 'type' => 'text'],
                ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['amount' => (float) $rows->sum('amount')],
            'total_label' => 'Total for the period · '.$rows->count().' '.($rows->count() === 1 ? 'payment' : 'payments'),
        ];
    }

    private function feesPending(array $filters, User $user): array
    {
        $asOn = Carbon::parse($filters['as_on'] ?? now())->endOfDay();

        $enquiries = Enquiry::query()
            ->visibleTo($user)
            ->with(['customer', 'tickets.service', 'payments'])
            ->whereHas('tickets', fn (Builder $tickets) => $tickets->where('status', TicketStatus::TaskCompleted->value))
            ->get();

        $rows = $enquiries->map(function (Enquiry $enquiry) use ($asOn, $filters, $user) {
            $completed = $enquiry->tickets
                ->filter(fn (Ticket $ticket) => $ticket->status === TicketStatus::TaskCompleted && $ticket->completed_at && $ticket->completed_at->lte($asOn))
                ->filter(fn (Ticket $ticket) => ! $user->isEmployee() || $ticket->assigned_to === $user->id)
                ->when(! empty($filters['service_id']), fn (Collection $tickets) => $tickets->where('service_id', (int) $filters['service_id']))
                ->when(! empty($filters['staff_id']), fn (Collection $tickets) => $tickets->where('assigned_to', (int) $filters['staff_id']));

            if ($completed->isEmpty()) {
                return null;
            }

            $paid = (float) $enquiry->payments->filter(fn (Payment $payment) => $payment->paid_at && $payment->paid_at->lte($asOn))->sum('amount');
            $due = max(0.0, (float) $enquiry->total - $paid);

            if ($due <= 0) {
                return null;
            }

            $since = $completed->min('completed_at');

            return [
                'client' => $enquiry->customer?->name ?? '—',
                'phone' => $enquiry->customer?->phone ?? '—',
                'enquiry' => ['text' => $enquiry->number, 'url' => route('enquiries.show', $enquiry)],
                'services' => $completed->pluck('service.name')->filter()->unique()->implode(', ') ?: '—',
                'completed_on' => $since->format('j M Y'),
                'days' => ['value' => (int) $since->copy()->startOfDay()->diffInDays($asOn->copy()->startOfDay())],
                'due' => $due,
            ];
        })->filter()->sortByDesc(fn (array $row) => $row['days']['value'])->values();

        return [
            'subtitle' => 'As on '.$asOn->format('j M Y').' · oldest first',
            'columns' => [
                ['key' => 'client', 'label' => 'Client', 'type' => 'strong'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['key' => 'enquiry', 'label' => 'Enquiry', 'type' => 'link'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'text'],
                ['key' => 'completed_on', 'label' => 'Completed on', 'type' => 'text'],
                ['key' => 'days', 'label' => 'Days pending', 'type' => 'days', 'align' => 'end', 'danger_at' => 15],
                ['key' => 'due', 'label' => 'Amount due', 'type' => 'money_red', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['due' => (float) $rows->sum('due')],
            'total_label' => 'Total · '.$rows->count().' '.($rows->count() === 1 ? 'enquiry' : 'enquiries'),
        ];
    }

    private function serviceSummary(array $filters, User $user): array
    {
        $tickets = $this->ticketQuery($filters, $user)->with(['service', 'enquiry.tickets', 'enquiry.payments'])->get();

        $rows = $tickets->groupBy('service_id')->map(function (Collection $group) {
            $shares = $group->map(fn (Ticket $ticket) => $this->share($ticket));

            return [
                'service' => $group->first()->service?->name ?? '—',
                'services' => $group->count(),
                'billed' => (float) $shares->sum('billed'),
                'received' => (float) $shares->sum('paid'),
                'pending' => (float) $shares->sum('balance'),
            ];
        })->sortByDesc('billed')->values();

        return [
            'subtitle' => $this->range($filters).($this->serviceName($filters) ? ' · '.$this->serviceName($filters) : ''),
            'columns' => [
                ['key' => 'service', 'label' => 'Service', 'type' => 'strong'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'text', 'align' => 'end'],
                ['key' => 'billed', 'label' => 'Billed', 'type' => 'money', 'align' => 'end'],
                ['key' => 'received', 'label' => 'Received', 'type' => 'money_green', 'align' => 'end'],
                ['key' => 'pending', 'label' => 'Pending', 'type' => 'money_red', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['services' => $rows->sum('services'), 'billed' => (float) $rows->sum('billed'), 'received' => (float) $rows->sum('received'), 'pending' => (float) $rows->sum('pending')],
            'total_label' => 'Total',
        ];
    }

    private function monthSummary(array $filters, User $user): array
    {
        $startYear = (int) ($filters['fy'] ?? $this->defaults('month_summary')['fy']);
        $start = Carbon::create($startYear, 4, 1)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();
        $lastMonth = now()->lt($end) ? now()->startOfMonth() : $end->copy()->startOfMonth();

        $tickets = Ticket::query()->visibleTo($user)
            ->whereBetween('created_at', [$start, $end])
            ->with(['enquiry.tickets', 'enquiry.payments'])
            ->get()
            ->groupBy(fn (Ticket $ticket) => $ticket->created_at->format('Y-m'));

        $rows = collect();

        for ($month = $start->copy(); $month->lte($lastMonth); $month->addMonth()) {
            $group = $tickets->get($month->format('Y-m'), collect());
            $shares = $group->map(fn (Ticket $ticket) => $this->share($ticket));

            $rows->push([
                'month' => $month->format('F Y'),
                'services' => $group->count(),
                'billed' => (float) $shares->sum('billed'),
                'received' => (float) $shares->sum('paid'),
                'pending' => (float) $shares->sum('balance'),
            ]);
        }

        $label = 'FY '.$startYear.'-'.substr((string) ($startYear + 1), -2);
        $partial = now()->lt($end);

        return [
            'subtitle' => $label.' · '.$start->format('F').' to '.$lastMonth->format('F').($partial ? ' so far' : ''),
            'columns' => [
                ['key' => 'month', 'label' => 'Month', 'type' => 'strong'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'text', 'align' => 'end'],
                ['key' => 'billed', 'label' => 'Billed', 'type' => 'money', 'align' => 'end'],
                ['key' => 'received', 'label' => 'Received', 'type' => 'money_green', 'align' => 'end'],
                ['key' => 'pending', 'label' => 'Pending', 'type' => 'money_red', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['services' => $rows->sum('services'), 'billed' => (float) $rows->sum('billed'), 'received' => (float) $rows->sum('received'), 'pending' => (float) $rows->sum('pending')],
            'total_label' => $partial ? 'Total so far' : 'Total',
        ];
    }

    // ------------------------------------------------------------------
    // Work
    // ------------------------------------------------------------------

    private function servicesByStatus(array $filters, User $user): array
    {
        $tickets = $this->ticketQuery($filters, $user)
            ->with(['customer', 'service', 'assignedTo'])
            ->latest('created_at')->latest('id')
            ->get();

        $rows = $tickets->map(fn (Ticket $ticket) => [
            'number' => ['text' => $ticket->number, 'url' => route('tickets.show', $ticket)],
            'client' => $ticket->customer?->name ?? '—',
            'service' => $ticket->service?->name ?? '—',
            'staff' => $ticket->assignedTo?->name ?? 'Unassigned',
            'status' => $ticket->status?->value,
            'created' => $ticket->created_at?->format('j M Y'),
            'days' => ['value' => $ticket->status?->isClosed() ? null : (int) $ticket->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay())],
        ]);

        return [
            'subtitle' => $this->range($filters).' · '.($filters['status'] ?? 'All statuses'),
            'columns' => [
                ['key' => 'number', 'label' => 'Service No.', 'type' => 'link'],
                ['key' => 'client', 'label' => 'Client', 'type' => 'strong'],
                ['key' => 'service', 'label' => 'Service', 'type' => 'text'],
                ['key' => 'staff', 'label' => 'Staff', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['key' => 'created', 'label' => 'Created on', 'type' => 'text'],
                ['key' => 'days', 'label' => 'Days open', 'type' => 'days', 'align' => 'end', 'danger_at' => 30],
            ],
            'rows' => $rows,
            'total' => [],
            'total_label' => 'Total services · '.$rows->count(),
        ];
    }

    private function staffWork(array $filters, User $user): array
    {
        $people = User::query()
            ->with('profile.designation')
            ->where('role', UserRole::Employee->value)
            ->when($user->isEmployee(), fn (Builder $query) => $query->whereKey($user->id))
            ->when(! empty($filters['staff_id']), fn (Builder $query) => $query->whereKey($filters['staff_id']))
            ->when(! empty($filters['designation_id']), fn (Builder $query) => $query->whereHas('profile', fn (Builder $profile) => $profile->where('designation_id', $filters['designation_id'])))
            ->orderBy('name')->get();

        $tickets = $this->ticketQuery(array_intersect_key($filters, array_flip(['from', 'to'])), $user)
            ->whereIn('assigned_to', $people->pluck('id'))
            ->with(['enquiry.tickets', 'enquiry.payments'])
            ->get()
            ->groupBy('assigned_to');

        $rows = $people->map(function (User $person) use ($tickets) {
            $group = $tickets->get($person->id, collect());
            $completed = $group->filter(fn (Ticket $ticket) => $ticket->status?->isClosed())->count();

            return [
                'staff' => $person->name,
                'designation' => $person->profile?->designation?->name ?? '—',
                'assigned' => $group->count(),
                'completed' => $completed,
                'open' => $group->count() - $completed,
                'on_hold' => $group->filter(fn (Ticket $ticket) => $ticket->status === TicketStatus::OnHold)->count(),
                'fees' => (float) $group->sum(fn (Ticket $ticket) => $this->share($ticket)['paid']),
            ];
        })->sortByDesc('assigned')->values();

        return [
            'subtitle' => $this->range($filters).' · '.($this->staffName($filters) ?? 'All staff'),
            'columns' => [
                ['key' => 'staff', 'label' => 'Staff', 'type' => 'strong'],
                ['key' => 'designation', 'label' => 'Designation', 'type' => 'text'],
                ['key' => 'assigned', 'label' => 'Assigned', 'type' => 'text', 'align' => 'end'],
                ['key' => 'completed', 'label' => 'Completed', 'type' => 'green', 'align' => 'end'],
                ['key' => 'open', 'label' => 'Open', 'type' => 'text', 'align' => 'end'],
                ['key' => 'on_hold', 'label' => 'On hold', 'type' => 'text', 'align' => 'end'],
                ['key' => 'fees', 'label' => 'Fees collected', 'type' => 'money', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['assigned' => $rows->sum('assigned'), 'completed' => $rows->sum('completed'), 'open' => $rows->sum('open'), 'on_hold' => $rows->sum('on_hold'), 'fees' => (float) $rows->sum('fees')],
            'total_label' => 'Total',
        ];
    }

    private function pendingDocuments(array $filters, User $user): array
    {
        $tickets = $this->ticketQuery($filters, $user)
            ->where('status', '!=', TicketStatus::TaskCompleted->value)
            ->with(['customer', 'assignedTo', 'service.documents', 'documents'])
            ->get();

        $rows = collect();

        foreach ($tickets as $ticket) {
            foreach ($ticket->service?->documents ?? [] as $serviceDocument) {
                $latest = $ticket->documents->where('service_document_id', $serviceDocument->id)->sortByDesc('version')->first();

                if ($latest && $latest->status !== 'Rejected') {
                    continue;
                }

                $since = $latest?->verified_at ?? $ticket->created_at;

                $rows->push([
                    'client' => $ticket->customer?->name ?? '—',
                    'phone' => $ticket->customer?->phone ?? '—',
                    'number' => ['text' => $ticket->number, 'url' => route('tickets.show', $ticket)],
                    'document' => $serviceDocument->name.($latest ? ' (re-upload)' : ''),
                    'type' => ['text' => $serviceDocument->is_mandatory ? 'Mandatory' : 'Optional', 'tone' => $serviceDocument->is_mandatory ? 'danger' : 'secondary'],
                    'days' => ['value' => (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay())],
                    'staff' => $ticket->assignedTo?->name ?? 'Unassigned',
                ]);
            }
        }

        $rows = $rows->sortByDesc(fn (array $row) => $row['days']['value'])->values();

        return [
            'subtitle' => $this->range($filters).' · longest waiting first',
            'columns' => [
                ['key' => 'client', 'label' => 'Client', 'type' => 'strong'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['key' => 'number', 'label' => 'Service No.', 'type' => 'link'],
                ['key' => 'document', 'label' => 'Document', 'type' => 'text'],
                ['key' => 'type', 'label' => 'Type', 'type' => 'chip'],
                ['key' => 'days', 'label' => 'Days waiting', 'type' => 'days', 'align' => 'end', 'danger_at' => 15],
                ['key' => 'staff', 'label' => 'Staff', 'type' => 'text'],
            ],
            'rows' => $rows,
            'total' => [],
            'total_label' => 'Total · '.$rows->count().' '.($rows->count() === 1 ? 'document' : 'documents'),
        ];
    }

    private function enquiries(array $filters, User $user): array
    {
        $enquiries = Enquiry::query()
            ->visibleTo($user)
            ->with(['customer', 'createdBy', 'tickets.service'])
            ->when(! empty($filters['from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['created_by']), fn (Builder $query) => $query->where('created_by', $filters['created_by']))
            ->when(! empty($filters['enquiry_status']), fn (Builder $query) => $query->where('status', $filters['enquiry_status']))
            ->latest('created_at')->latest('id')
            ->get();

        $rows = $enquiries->map(fn (Enquiry $enquiry) => [
            'date' => $enquiry->created_at?->format('j M Y'),
            'enquiry' => ['text' => $enquiry->number, 'url' => route('enquiries.show', $enquiry)],
            'client' => $enquiry->customer?->name ?? '—',
            'services' => $enquiry->tickets->filter(fn (Ticket $ticket) => ! $user->isEmployee() || $ticket->assigned_to === $user->id)->pluck('service.name')->filter()->implode(', ') ?: '—',
            'created_by' => $enquiry->createdBy?->name ?? '—',
            'status' => ['text' => $enquiry->status ?? 'Open', 'tone' => ($enquiry->status ?? 'Open') === 'Closed' ? 'success' : 'info'],
            'total' => (float) $enquiry->total,
        ]);

        return [
            'subtitle' => $this->range($filters).(! empty($filters['enquiry_status']) ? ' · '.$filters['enquiry_status'] : ''),
            'columns' => [
                ['key' => 'date', 'label' => 'Date', 'type' => 'text'],
                ['key' => 'enquiry', 'label' => 'Enquiry', 'type' => 'link'],
                ['key' => 'client', 'label' => 'Client', 'type' => 'strong'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'text'],
                ['key' => 'created_by', 'label' => 'Created by', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'chip'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'align' => 'end'],
            ],
            'rows' => $rows,
            'total' => ['total' => (float) $rows->sum('total')],
            'total_label' => 'Total · '.$rows->count().' '.($rows->count() === 1 ? 'enquiry' : 'enquiries'),
        ];
    }

    // ------------------------------------------------------------------
    // Clients
    // ------------------------------------------------------------------

    private function clientHistory(array $filters, User $user): array
    {
        $client = ! empty($filters['client_id']) ? Customer::query()->find($filters['client_id']) : null;

        $columns = [
            ['key' => 'date', 'label' => 'Date', 'type' => 'text'],
            ['key' => 'number', 'label' => 'Service No.', 'type' => 'link'],
            ['key' => 'service', 'label' => 'Service', 'type' => 'text'],
            ['key' => 'staff', 'label' => 'Staff', 'type' => 'text'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ['key' => 'fee', 'label' => 'Fee', 'type' => 'money', 'align' => 'end'],
            ['key' => 'paid', 'label' => 'Paid', 'type' => 'money_green', 'align' => 'end'],
            ['key' => 'balance', 'label' => 'Balance', 'type' => 'money_red', 'align' => 'end'],
        ];

        if (! $client) {
            return [
                'subtitle' => $this->range($filters),
                'columns' => $columns,
                'rows' => collect(),
                'total' => [],
                'total_label' => 'Total',
                'client' => null,
                'note' => 'There are no clients to show yet.',
            ];
        }

        $tickets = $this->ticketQuery(array_intersect_key($filters, array_flip(['from', 'to'])), $user)
            ->where('customer_id', $client->id)
            ->with(['service', 'assignedTo', 'enquiry.tickets', 'enquiry.payments'])
            ->latest('created_at')->latest('id')
            ->get();

        $rows = $tickets->map(function (Ticket $ticket) {
            $share = $this->share($ticket);

            return [
                'date' => $ticket->created_at?->format('j M Y'),
                'number' => ['text' => $ticket->number, 'url' => route('tickets.show', $ticket)],
                'service' => $ticket->service?->name ?? '—',
                'staff' => $ticket->assignedTo?->name ?? 'Unassigned',
                'status' => $ticket->status?->value,
                'fee' => $share['billed'],
                'paid' => $share['paid'],
                'balance' => $share['balance'],
            ];
        });

        return [
            'subtitle' => $this->range($filters),
            'columns' => $columns,
            'rows' => $rows,
            'total' => ['fee' => (float) $rows->sum('fee'), 'paid' => (float) $rows->sum('paid'), 'balance' => (float) $rows->sum('balance')],
            'total_label' => 'Total · '.$rows->count().' '.($rows->count() === 1 ? 'service' : 'services'),
            'client' => $client,
            'note' => null,
        ];
    }

    private function serviceName(array $filters): ?string
    {
        return ! empty($filters['service_id']) ? Service::query()->whereKey($filters['service_id'])->value('name') : null;
    }

    private function staffName(array $filters): ?string
    {
        return ! empty($filters['staff_id']) ? User::query()->whereKey($filters['staff_id'])->value('name') : null;
    }
}
