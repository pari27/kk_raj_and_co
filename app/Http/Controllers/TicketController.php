<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EnquiryStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    private const CLOSED_STATUSES = ['Task Completed'];

    public function index(): View
    {
        $this->authorize('viewAny', Ticket::class);

        $user = Auth::user();

        $base = Ticket::query()->with(['service.documents', 'customer', 'assignedTo', 'enquiry.payments', 'documents']);

        if ($user->isEmployee()) {
            $base->where('assigned_to', $user->id);
        }

        $pending = (clone $base)->whereNotIn('status', self::CLOSED_STATUSES)->latest('created_at')->get();
        $history = (clone $base)->whereIn('status', self::CLOSED_STATUSES)->latest('completed_at')->get();

        $stats = [
            'pending_total' => $pending->count(),
            'waiting_for_client' => $pending->whereIn('status', [TicketStatus::DocumentsPending, TicketStatus::PartiallyReceived, TicketStatus::AdditionalDocumentsRequired])->count(),
            'in_progress' => $pending->where('status', TicketStatus::WorkInProgress)->count(),
            'awaiting_payment' => $pending->where('status', TicketStatus::TaskCompleted)->count(),
            'closed_total' => $history->count(),
            'closed_this_month' => $history->filter(fn ($t) => $t->completed_at && $t->completed_at->isCurrentMonth())->count(),
            'fees_collected' => $history->pluck('enquiry')->unique('id')->sum(fn ($enquiry) => $enquiry->amountPaid()),
            'avg_days' => $history->count()
                ? (int) round($history->average(fn ($t) => $t->created_at->diffInDays($t->completed_at)))
                : 0,
        ];

        return view('tickets.index', compact('pending', 'history', 'stats'));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['service.documents', 'customer', 'enquiry.tickets', 'enquiry.payments', 'assignedTo', 'createdBy', 'documents.uploadedBy', 'documents.verifiedBy']);

        $employees = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin, UserRole::Employee])
            ->where('is_active', true)
            ->orderByRaw('CASE role WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [UserRole::Admin->value, UserRole::SuperAdmin->value])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $history = AuditLog::query()
            ->where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->with('user')
            ->oldest('created_at')
            ->get();

        $documentRows = $ticket->service->documents->map(function ($serviceDocument) use ($ticket) {
            $versions = $ticket->documents
                ->where('service_document_id', $serviceDocument->id)
                ->sortByDesc('version')
                ->values();

            return [
                'serviceDocument' => $serviceDocument,
                'current' => $versions->first(),
                'versions' => $versions,
            ];
        });

        return view('tickets.show', compact('ticket', 'employees', 'history', 'documentRows'));
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateStatus', $ticket);

        abort_if($ticket->status->isClosed(), 422, "Status can't be changed after task completion.");

        $before = $ticket->status;
        $status = TicketStatus::from($request->validated('status'));

        $ticket->update([
            'status' => $status,
            'completed_at' => $status->isClosed() ? ($ticket->completed_at ?? now()) : null,
        ]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Ticket',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Status {$before->value} → {$status->value}",
            subject: $ticket,
        );

        EnquiryStatusService::recompute($ticket->enquiry);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->number} is now \"{$status->value}\".");
    }

    public function reassign(Ticket $ticket): RedirectResponse
    {
        $this->authorize('reassign', Ticket::class);

        $assignableRoles = [UserRole::Admin->value, UserRole::SuperAdmin->value, UserRole::Employee->value];
        $validated = Validator::make(request()->all(), [
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->whereIn('role', $assignableRoles)
                    ->where('is_active', true),
            ],
        ])->validate();

        $ticket->update(['assigned_to' => $validated['assigned_to'] ?? null]);

        $label = $ticket->assignedTo?->name ?? 'Unassigned';

        AuditLogger::log(
            action: 'Updated',
            module: 'Ticket',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Reassigned to {$label}",
            subject: $ticket,
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->number} is now assigned to {$label}.");
    }
}
