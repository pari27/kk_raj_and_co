<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketDocumentRequest;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TicketDocumentController extends Controller
{
    public function store(StoreTicketDocumentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('uploadDocument', $ticket);

        $serviceDocument = ServiceDocument::findOrFail($request->validated('service_document_id'));

        abort_unless($serviceDocument->service_id === $ticket->service_id, 404);

        $nextVersion = 1 + (int) TicketDocument::query()
            ->where('ticket_id', $ticket->id)
            ->where('service_document_id', $serviceDocument->id)
            ->max('version');

        $file = $request->file('file');
        $path = $file->store('ticket-documents', 'public');

        TicketDocument::create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
            'version' => $nextVersion,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'Awaiting check',
            'uploaded_by' => Auth::id(),
        ]);

        AuditLogger::log(
            action: 'Created',
            module: 'Document',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: ($nextVersion > 1 ? 'Re-uploaded ' : 'Uploaded ').$serviceDocument->name." (v{$nextVersion}) on {$ticket->number}",
            subject: $ticket,
        );

        $this->recomputeDocumentStatus($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "\"{$serviceDocument->name}\" uploaded.");
    }

    public function verify(Ticket $ticket, TicketDocument $document): RedirectResponse
    {
        $this->authorize('verifyDocument', $ticket);

        abort_unless($document->ticket_id === $ticket->id, 404);

        $document->update([
            'status' => 'Verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Document',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Verified {$document->serviceDocument->name} (v{$document->version}) on {$ticket->number}",
            subject: $ticket,
        );

        $this->recomputeDocumentStatus($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "\"{$document->serviceDocument->name}\" verified.");
    }

    public function reject(Request $request, Ticket $ticket, TicketDocument $document): RedirectResponse
    {
        $this->authorize('verifyDocument', $ticket);

        abort_unless($document->ticket_id === $ticket->id, 404);

        $validated = Validator::make($request->all(), [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ])->validate();

        $document->update([
            'status' => 'Rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Document',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Rejected {$document->serviceDocument->name} (v{$document->version}) on {$ticket->number} — {$validated['rejection_reason']}",
            subject: $ticket,
        );

        $this->recomputeDocumentStatus($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "\"{$document->serviceDocument->name}\" rejected.");
    }

    /**
     * Keep a ticket's status in sync with its document checklist, as long as
     * it hasn't already been manually moved on to a later workflow stage.
     */
    private function recomputeDocumentStatus(Ticket $ticket): void
    {
        $autoManagedStatuses = [
            TicketStatus::DocumentsPending,
            TicketStatus::PartiallyReceived,
            TicketStatus::DocumentsReceived,
            TicketStatus::UnderVerification,
            TicketStatus::AdditionalDocumentsRequired,
        ];

        if (! in_array($ticket->status, $autoManagedStatuses, true)) {
            return;
        }

        $ticket->load(['service.documents', 'documents']);

        $latestByDocument = $ticket->documents
            ->groupBy('service_document_id')
            ->map(fn ($versions) => $versions->sortByDesc('version')->first());

        $hasAnyUpload = $latestByDocument->isNotEmpty();
        $hasRejected = $latestByDocument->contains(fn (TicketDocument $document): bool => $document->status === 'Rejected');

        $mandatoryDocuments = $ticket->service->documents->where('is_mandatory', true);
        $mandatoryUploadedCount = $mandatoryDocuments->filter(
            fn (ServiceDocument $serviceDocument): bool => $latestByDocument->has($serviceDocument->id)
        )->count();

        $allMandatoryUploaded = $mandatoryUploadedCount === $mandatoryDocuments->count();
        $someMandatoryUploaded = $mandatoryUploadedCount > 0;
        $allMandatoryVerified = $mandatoryDocuments->isNotEmpty()
            && $mandatoryDocuments->every(
                fn (ServiceDocument $serviceDocument): bool => $latestByDocument->get($serviceDocument->id)?->status === 'Verified'
            );

        $status = match (true) {
            $hasRejected => TicketStatus::AdditionalDocumentsRequired,
            $allMandatoryVerified => TicketStatus::UnderVerification,
            $allMandatoryUploaded && $hasAnyUpload => TicketStatus::DocumentsReceived,
            $someMandatoryUploaded => TicketStatus::PartiallyReceived,
            default => TicketStatus::DocumentsPending,
        };

        if ($status === $ticket->status) {
            return;
        }

        $before = $ticket->status;
        $ticket->update(['status' => $status]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Ticket',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Status {$before->value} → {$status->value}",
            subject: $ticket,
        );
    }
}
