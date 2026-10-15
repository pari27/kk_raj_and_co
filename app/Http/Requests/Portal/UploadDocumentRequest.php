<?php

namespace App\Http\Requests\Portal;

use App\Http\Requests\StoreTicketDocumentRequest;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

/**
 * A client sending a document. Same file rules as staff uploads (type and size
 * come from the document's setting), plus: it must be one of the client's own
 * services, and a closed service takes nothing more.
 */
class UploadDocumentRequest extends StoreTicketDocumentRequest
{
    public function authorize(): bool
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        // Not theirs: behave as if it doesn't exist.
        abort_unless($ticket->customer_id === Auth::guard('customer')->id(), 404);

        return ! $ticket->status->isClosed();
    }
}
