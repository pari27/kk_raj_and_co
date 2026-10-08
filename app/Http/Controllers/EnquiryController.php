<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreEnquiryRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
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
            Enquiry::query()->with(['customer', 'createdBy', 'tickets.service'])
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
            'open_enquiries' => $scopeToOwnTickets(Enquiry::query())->where('status', 'Open')->count(),
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

        if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin()) {
            $adminAssignees = User::query()
                ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
                ->where('is_active', true)
                ->orderByRaw('CASE role WHEN ? THEN 0 ELSE 1 END', [UserRole::Admin->value])
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
                'number' => NumberSequence::next('enquiries', 'ENQ'),
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
                    'number' => NumberSequence::next('tickets', 'TKT'),
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

        if ($customerWasCreated) {
            AuditLogger::log(
                action: 'Created',
                module: 'Customer',
                recordLabel: $customer->name,
                recordUrl: route('customers.show', $customer),
                subject: $customer,
            );
        }

        AuditLogger::log(
            action: 'Created',
            module: 'Enquiry',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: "For {$customer->name} — ".count($lines).' ticket(s)',
            subject: $enquiry,
        );

        foreach ($createdTickets as $ticket) {
            AuditLogger::log(
                action: 'Created',
                module: 'Ticket',
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

    public function show(Enquiry $enquiry): View
    {
        $this->authorize('view', $enquiry);

        $enquiry->load(['customer', 'createdBy', 'tickets.service', 'tickets.assignedTo', 'payments']);

        $closedTicketsCount = $enquiry->tickets
            ->filter(fn (Ticket $ticket) => $ticket->status->isClosed())
            ->count();

        $feesReceived = $enquiry->amountPaid();
        $feesReceivedCount = $enquiry->payments->count();
        $feesPending = $enquiry->balanceDue();

        $ticketIds = $enquiry->tickets->pluck('id');

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
        ));
    }
}
