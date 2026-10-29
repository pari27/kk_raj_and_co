<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;

class TicketDocumentStatusService
{
    /**
     * Keep a ticket's status in sync with its document checklist, as long as
     * it hasn't already been manually moved on to a later workflow stage.
     */
    public static function recompute(Ticket $ticket): void
    {
        $autoManagedStatuses = [
            TicketStatus::DocumentsPending,
            TicketStatus::PartiallyReceived,
            TicketStatus::DocumentsReceived,
            TicketStatus::DocumentsVerified,
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
            $allMandatoryVerified => TicketStatus::DocumentsVerified,
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
            module: 'Service',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: "Status {$before->value} → {$status->value}",
            subject: $ticket,
        );
    }
}
