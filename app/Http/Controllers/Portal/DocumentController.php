<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UploadDocumentRequest;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Services\AuditLogger;
use App\Services\ClientPortalService;
use App\Services\TicketDocumentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    /**
     * A client sends a document (a file, or text for documents that ask for a typed value).
     * A new send never replaces an old one: it is saved as the next version.
     */
    public function store(UploadDocumentRequest $request, Ticket $ticket): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $serviceDocument = ServiceDocument::findOrFail($request->validated('service_document_id'));

        abort_unless($serviceDocument->service_id === $ticket->service_id, 404);

        $row = $this->portal->documentRows($ticket->load(['service.documents', 'documents']))
            ->firstWhere('serviceDocument.id', $serviceDocument->id);

        // Only what is still missing, or was rejected, can be sent.
        abort_unless($row, 404);

        if (! $row['needsUpload']) {
            $name = $serviceDocument->name;

            $message = match (true) {
                $ticket->status->isClosed() => 'This service is complete, so documents can no longer be sent.',
                $row['current']?->status === 'Verified' => "\"{$name}\" has already been checked and accepted. You do not need to send it again.",
                default => "\"{$name}\" has already been sent and is waiting to be checked.",
            };

            return redirect()->route('customer.tickets.show', $ticket)->with('error', $message);
        }

        $nextVersion = 1 + (int) TicketDocument::query()
            ->where('ticket_id', $ticket->id)
            ->where('service_document_id', $serviceDocument->id)
            ->max('version');

        $file = $request->file('file');
        $path = $file?->store('ticket-documents', 'public');

        TicketDocument::create([
            'ticket_id' => $ticket->id,
            'service_document_id' => $serviceDocument->id,
            'version' => $nextVersion,
            'file_path' => $path,
            'original_filename' => $file?->getClientOriginalName(),
            'text_value' => $serviceDocument->isText() ? $request->validated('text_value') : null,
            'status' => 'Awaiting check',
            'uploaded_by_customer_id' => $customer->id,
        ]);

        AuditLogger::log(
            action: 'Created',
            module: 'Document',
            recordLabel: $ticket->number,
            recordUrl: route('tickets.show', $ticket),
            details: ($nextVersion > 1 ? 'Re-uploaded ' : 'Uploaded ').$serviceDocument->name." (v{$nextVersion}) on {$ticket->number} by the client",
            subject: $ticket,
        );

        TicketDocumentStatusService::recompute($ticket);

        return redirect()
            ->route('customer.tickets.show', $ticket)
            ->with('status', "\"{$serviceDocument->name}\" sent. Thank you, we will check it soon.");
    }

    /**
     * Show one of the client's own files in the browser (PDFs and images open in a tab).
     */
    public function view(Ticket $ticket, TicketDocument $document): \Symfony\Component\HttpFoundation\Response
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($ticket->customer_id === $customer->id && $document->ticket_id === $ticket->id, 404);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->response($document->file_path, $document->original_filename);
    }

    /**
     * Download one of the client's own uploaded files.
     */
    public function download(Ticket $ticket, TicketDocument $document): StreamedResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($ticket->customer_id === $customer->id && $document->ticket_id === $ticket->id, 404);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }
}
