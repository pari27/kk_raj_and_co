<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\AddEnquiryServiceRequest;
use App\Http\Requests\StoreEnquiryRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AssignmentMails;
use App\Services\EnquiryStatusService;
use App\Services\EventMailer;
use App\Models\Setting;
use App\Support\NumberSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Enquiry::class);

        $user = Auth::user();
        $scopeToOwnTickets = fn ($query) => $query->when(
            $user->isEmployee(),
            fn ($query) => $query->whereHas('tickets', fn ($query) => $query->where('assigned_to', $user->id))
        );

        $enquiries = $scopeToOwnTickets(
            Enquiry::query()->with(['customer', 'createdBy', 'tickets.service', 'payments'])
        )->latest('created_at')->latest('id')->get();

        $today = now();

        $stats = [
            'enquiries_this_month' => $scopeToOwnTickets(Enquiry::query())
                ->whereYear('created_at', $today->year)
                ->whereMonth('created_at', $today->month)
                ->count(),
            'tickets_created' => Ticket::query()
                ->when($user->isEmployee(), fn ($query) => $query->where('assigned_to', $user->id))
                ->whereYear('created_at', $today->year)
                ->whereMonth('created_at', $today->month)
                ->count(),
            'enquiry_value' => $scopeToOwnTickets(Enquiry::query())
                ->whereYear('created_at', $today->year)
                ->whereMonth('created_at', $today->month)
                ->sum('total'),
            'open_services' => Ticket::query()
                ->when($user->isEmployee(), fn ($query) => $query->where('assigned_to', $user->id))
                ->where('status', '!=', TicketStatus::TaskCompleted->value)
                ->count(),
            'pending_fees' => (int) round($enquiries->sum(fn (Enquiry $enquiry) => $enquiry->balanceDue())),
        ];

        return view('enquiries.index', compact('enquiries', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Enquiry::class);

        $services = Service::query()->where('is_active', true)->with('documents')->withCount('documents')->orderBy('name')->get();

        $employees = User::query()
            ->where('role', UserRole::Employee)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        if (Auth::user()->isAdmin()) {
            $adminAssignees = User::query()
                ->whereIn('role', [UserRole::Admin])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'role']);

            $employees = $adminAssignees->concat($employees)->values();
        }

        $canManagePricing = Auth::user()->can('managePricing', Enquiry::class);
        $canAssignTickets = Auth::user()->can('assignTickets', Enquiry::class);

        return view('enquiries.create', compact('services', 'employees', 'canManagePricing', 'canAssignTickets'));
    }

    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        $this->authorize('create', Enquiry::class);

        $user = Auth::user();
        $canManagePricing = $user->can('managePricing', Enquiry::class);

        $lines = $request->validated('services');
        $services = Service::query()->whereIn('id', array_column($lines, 'service_id'))->get()->keyBy('id');

        $createdTickets = [];
        $customer = null;
        $customerWasCreated = false;

        AssignmentMails::collect();

        $enquiry = DB::transaction(function () use ($request, $lines, $services, $user, $canManagePricing, &$createdTickets, &$customer, &$customerWasCreated) {
            $emailNotificationsEnabled = $request->boolean('customer_email_notifications');

            if ($request->filled('customer_id')) {
                $customer = Customer::findOrFail($request->validated('customer_id'));
                $updates = ['email_notifications_enabled' => $emailNotificationsEnabled];

                if (! $customer->email && $request->filled('customer_email')) {
                    $updates['email'] = $request->validated('customer_email');
                }

                $customer->update($updates);
            } else {
                $customer = Customer::create([
                    'name' => $request->validated('customer_name'),
                    'phone' => $request->validated('customer_phone'),
                    'email' => $request->validated('customer_email'),
                    'email_notifications_enabled' => $emailNotificationsEnabled,
                    'portal_status' => 'not_logged_in',
                    'is_active' => true,
                    'created_by' => $user->id,
                ]);
                $customerWasCreated = true;
            }

            $subtotal = 0;
            $gstTotal = 0;
            $ticketRows = [];

            foreach ($lines as $line) {
                $service = $services->get($line['service_id']);
                $overridePrice = $canManagePricing && isset($line['price']) ? (float) $line['price'] : null;
                $breakdown = $service->priceBreakdown($overridePrice ?? (float) $service->default_price);

                $subtotal += $breakdown['price'];
                $gstTotal += $breakdown['gst_amount'];

                $assignedTo = match (true) {
                    ! empty($line['assigned_to']) => (int) $line['assigned_to'],
                    $user->isEmployee() => $user->id,
                    default => null,
                };

                $ticketRows[] = [
                    'service' => $service,
                    'assigned_to' => $assignedTo,
                    ...$breakdown,
                ];
            }

            $discount = $canManagePricing ? (float) ($request->validated('discount') ?? 0) : 0;
            $total = max(0, round($subtotal + $gstTotal - $discount, 2));

            $enquiry = Enquiry::create([
                'number' => NumberSequence::next('enquiries', Setting::enquiryPrefix()),
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'notes' => $request->validated('notes'),
                'subtotal' => $subtotal,
                'gst_total' => $gstTotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'Open',
            ]);

            foreach ($ticketRows as $row) {
                $createdTickets[] = Ticket::create([
                    'number' => NumberSequence::next('tickets', Setting::servicePrefix()),
                    'enquiry_id' => $enquiry->id,
                    'service_id' => $row['service']->id,
                    'customer_id' => $customer->id,
                    'assigned_to' => $row['assigned_to'],
                    'created_by' => $user->id,
                    'price' => $row['price'],
                    'gst_percent' => $row['service']->gst_percent,
                    'gst_amount' => $row['gst_amount'],
                    'total' => $row['total'],
                    'status' => 'Documents Pending',
                ]);
            }

            return $enquiry;
        });

        // Saved: now send the gathered "service assigned" emails, one per person.
        AssignmentMails::flush($createdTickets);

        if ($customerWasCreated) {
            AuditLogger::log(
                action: 'Created',
                module: 'Customer',
                recordLabel: $customer->name,
                recordUrl: route('customers.show', $customer),
                subject: $customer,
            );

            EventMailer::welcomeClient($customer);
        }

        AuditLogger::log(
            action: 'Created',
            module: 'Enquiry',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: "For {$customer->name} — ".count($lines).' service(s)',
            subject: $enquiry,
        );

        EventMailer::requestDocuments($customer, $enquiry->number, $createdTickets);

        foreach ($createdTickets as $ticket) {
            AuditLogger::log(
                action: 'Created',
                module: 'Service',
                recordLabel: $ticket->number,
                recordUrl: route('tickets.show', $ticket),
                details: $ticket->assigned_to
                    ? 'Assigned to '.($ticket->assignedTo?->name ?? 'employee')
                    : 'Unassigned',
                subject: $ticket,
                userId: $user->id,
            );
        }

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', "Enquiry {$enquiry->number} was created.");
    }

    /**
     * Add one more service to an enquiry that is still open. The fee is recalculated; any discount stays as it was.
     */
    public function addService(AddEnquiryServiceRequest $request, Enquiry $enquiry): RedirectResponse
    {
        $user = $request->user();
        $service = Service::query()->where('is_active', true)->findOrFail($request->validated('service_id'));
        $override = $request->filled('price') ? (float) $request->validated('price') : null;
        $breakdown = $service->priceBreakdown($override ?? (float) $service->default_price);

        $ticket = DB::transaction(function () use ($enquiry, $service, $breakdown, $request, $user) {
            $ticket = Ticket::create([
                'number' => NumberSequence::next('tickets', Setting::servicePrefix()),
                'enquiry_id' => $enquiry->id,
                'service_id' => $service->id,
                'customer_id' => $enquiry->customer_id,
                'assigned_to' => $request->validated('assigned_to') ?: null,
                'created_by' => $user->id,
                'price' => $breakdown['price'],
                'gst_percent' => $service->gst_percent,
                'gst_amount' => $breakdown['gst_amount'],
                'total' => $breakdown['total'],
                'status' => 'Documents Pending',
            ]);

            // The part of the service's total that is not GST (the typed price already includes GST for a GST-included service).
            $subtotal = (float) $enquiry->subtotal + ($breakdown['total'] - $breakdown['gst_amount']);
            $gstTotal = (float) $enquiry->gst_total + $breakdown['gst_amount'];

            $enquiry->update([
                'subtotal' => $subtotal,
                'gst_total' => $gstTotal,
                'total' => max(0, round($subtotal + $gstTotal - (float) $enquiry->discount, 2)),
            ]);

            return $ticket;
        });

        EnquiryStatusService::recompute($enquiry);

        AuditLogger::log(
            action: 'Created',
            module: 'Service',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: 'Added to '.$enquiry->number.($ticket->assigned_to ? ' · Assigned to '.($ticket->assignedTo?->name ?? 'employee') : ' · Unassigned'),
            subject: $ticket,
        );

        AuditLogger::log(
            action: 'Updated',
            module: 'Enquiry',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: "Added service {$service->name} ({$ticket->number}) — enquiry total now ₹".number_format((float) $enquiry->fresh()->total, 2),
            subject: $enquiry,
        );

        // Ask the client for this service's documents, as when the enquiry was first created.
        EventMailer::requestDocuments($enquiry->customer, $enquiry->number, [$ticket]);

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', "{$service->name} ({$ticket->number}) was added to {$enquiry->number}.");
    }
    public function show(Enquiry $enquiry): View
    {
        $this->authorize('view', $enquiry);

        $user = auth()->user();

        $enquiry->load([
            'customer',
            'createdBy',
            'tickets' => fn ($query) => $query->visibleTo($user),
            'tickets.service',
            'tickets.assignedTo',
            'payments',
        ]);

        $closedTicketsCount = $enquiry->tickets
            ->filter(fn (Ticket $ticket) => $ticket->status->isClosed())
            ->count();

        $feesReceived = $enquiry->amountPaid();
        $feesReceivedCount = $enquiry->payments->count();
        $feesPending = $enquiry->balanceDue();

        $ticketIds = $enquiry->tickets->pluck('id');

        $canAddService = $user->can('addService', $enquiry);
        $addService = null;

        if ($canAddService) {
            $palette = [
                ['bg' => '#e0edff', 'text' => '#2f5fbe'],
                ['bg' => '#e5f5e0', 'text' => '#2f8f3e'],
                ['bg' => '#ece5fb', 'text' => '#6d5bd0'],
                ['bg' => '#fbe5ea', 'text' => '#b91c4a'],
                ['bg' => '#fdecd2', 'text' => '#b9650a'],
            ];

            // How many open services each person already has.
            $openCounts = Ticket::query()
                ->whereNotIn('status', [TicketStatus::TaskCompleted->value])
                ->whereNotNull('assigned_to')
                ->selectRaw('assigned_to, count(*) as open_count')
                ->groupBy('assigned_to')
                ->pluck('open_count', 'assigned_to');

            $addService = [
                // Services already in this enquiry are not offered again.
                'services' => Service::query()
                    ->where('is_active', true)
                    ->whereNotIn('id', $enquiry->tickets()->pluck('service_id'))
                    ->with('documents:id,service_id,name')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Service $service) => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => (float) $service->default_price,
                        'gst_percent' => (float) $service->gst_percent,
                        'includes_gst' => (bool) $service->price_includes_gst,
                        'documents' => $service->documents->pluck('name')->values()->all(),
                    ])->values()->all(),
                'assignees' => User::query()
                    ->whereIn('role', [UserRole::Admin, UserRole::Employee])
                    ->where('is_active', true)
                    ->orderByRaw('CASE role WHEN ? THEN 0 ELSE 1 END', [UserRole::Admin->value])
                    ->orderBy('name')
                    ->get(['id', 'name', 'role'])
                    ->values()
                    ->map(function (User $assignee, int $i) use ($palette, $openCounts, $user) {
                        $words = preg_split('/\s+/', trim($assignee->name));

                        return [
                            'id' => $assignee->id,
                            'label' => $assignee->id === $user->id
                                ? $assignee->name.' (me)'
                                : ($assignee->role === UserRole::Employee ? $assignee->name : $assignee->role->label().' — '.$assignee->name),
                            'initials' => strtoupper(substr($words[0] ?? '', 0, 1).substr($words[1] ?? '', 0, 1)),
                            'color' => $palette[$i % count($palette)],
                            'open' => (int) ($openCounts[$assignee->id] ?? 0),
                        ];
                    })->all(),
                // What the new service will be called, and what the enquiry totals now.
                'next_number' => NumberSequence::next('tickets', Setting::servicePrefix()),
                'enquiry_total' => (float) $enquiry->total,
                'client_first_name' => strtok(trim((string) $enquiry->customer->name), ' ') ?: 'The client',
                'client_gets_email' => (bool) ($enquiry->customer->email && $enquiry->customer->email_notifications_enabled),
            ];
        }
        $activity = AuditLog::query()
            ->where(function ($query) use ($enquiry, $ticketIds) {
                $query->where(fn ($q) => $q->where('subject_type', Enquiry::class)->where('subject_id', $enquiry->id))
                    ->orWhere(fn ($q) => $q->where('subject_type', Ticket::class)->whereIn('subject_id', $ticketIds));
            })
            ->with('user')
            ->latest('created_at')
            ->get();

        return view('enquiries.show', compact(
            'enquiry',
            'closedTicketsCount',
            'feesReceived',
            'feesReceivedCount',
            'feesPending',
            'activity',
            'canAddService',
            'addService',
        ));
    }
}
