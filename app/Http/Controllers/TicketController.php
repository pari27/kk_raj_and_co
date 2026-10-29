<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\ClientNotification;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EnquiryStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $pending = (clone $base)->whereNotIn('status', self::CLOSED_STATUSES)->latest('created_at')->latest('id')->get();
        $history = (clone $base)->whereIn('status', self::CLOSED_STATUSES)->latest('completed_at')->get();

        $stats = [
            'pending_total' => $pending->count(),
            'waiting_for_client' => $pending->whereIn('status', [TicketStatus::DocumentsPending, TicketStatus::PartiallyReceived, TicketStatus::AdditionalDocumentsRequired])->count(),
            'in_progress' => $pending->where('status', TicketStatus::WorkInProgress)->count(),
            'awaiting_payment' => (int) round($pending->concat($history)->pluck('enquiry')->filter()->unique('id')->sum(fn ($enquiry) => $enquiry->balanceDue())),
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
            ->whereIn('role', [UserRole::Admin, UserRole::Employee])
            ->where('is_active', true)
            ->orderByRaw('CASE role WHEN ? THEN 0 ELSE 1 END', [UserRole::Admin->value])
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

        $comment = trim($request->validated('comment'));

        $ticket->update([
            'status' => $status,
            'status_comment' => $comment,
            'status_comment_for' => $status->value,
            'completed_at' => $status->isClosed() ? ($ticket->completed_at ?? now()) : null,
        ]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Service',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Status {$before->value} → {$status->value} — {$comment}",
            subject: $ticket,
        );

        EnquiryStatusService::recompute($ticket->enquiry);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Service {$ticket->number} is now \"{$status->value}\".");
    }

    /**
     * Delete a service that no document has been submitted for yet. The enquiry's fee is worked out again from its other services.
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        if ($reason = $ticket->deletionBlocker()) {
            return redirect(url()->previous(route('tickets.show', $ticket)))->with('error', $reason);
        }

        $enquiry = $ticket->enquiry()->with(['tickets', 'payments'])->first();
        $number = $ticket->number;
        $serviceName = $ticket->service?->name ?? 'Service';
        $staffUrl = route('tickets.show', $ticket);
        $clientUrl = route('customer.tickets.show', $ticket);
        $fees = $enquiry->feesWithout($ticket);

        DB::transaction(function () use ($ticket, $enquiry, $fees, $staffUrl, $clientUrl) {
            // Notifications that point at this service would lead nowhere now.
            AppNotification::query()->where('url', $staffUrl)->delete();
            ClientNotification::query()->where('url', $clientUrl)->delete();

            $ticket->delete();
            $enquiry->update($fees);
        });

        EnquiryStatusService::recompute($enquiry->fresh());

        AuditLogger::log(
            action: 'Deleted',
            module: 'Service',
            recordLabel: $number,
            details: "{$serviceName} removed from {$enquiry->number}",
        );

        AuditLogger::log(
            action: 'Updated',
            module: 'Enquiry',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: "Deleted service {$serviceName} ({$number}) — enquiry total now ₹".number_format($fees['total'], 2),
            subject: $enquiry,
        );

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', "{$serviceName} ({$number}) was deleted from {$enquiry->number}.");
    }
    public function reassign(Ticket $ticket): RedirectResponse
    {
        $this->authorize('reassign', Ticket::class);

        abort_if($ticket->status->isClosed(), 422, "A service can't be reassigned after task completion.");

        $assignableRoles = [UserRole::Admin->value, UserRole::Employee->value];
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
            module: 'Service',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Reassigned to {$label}",
            subject: $ticket,
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Service {$ticket->number} is now assigned to {$label}.");
    }
}
