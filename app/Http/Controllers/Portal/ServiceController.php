<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\ClientPortalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ClientPortalService $portal) {}

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('customer.tickets.index', [
            'services' => $this->portal->services($customer),
            'portal' => $this->portal,
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $customer = Auth::guard('customer')->user();

        // Someone else's service looks exactly like one that doesn't exist.
        abort_unless($ticket->customer_id === $customer->id, 404);

        $ticket->load(['service.documents', 'documents', 'enquiry.payments', 'enquiry.tickets', 'assignedTo.profile.designation']);

        $rows = $this->portal->documentRows($ticket);

        return view('customer.tickets.show', [
            'ticket' => $ticket,
            'stage' => $this->portal->stage($ticket),
            'steps' => ClientPortalService::STEPS,
            'documentRows' => $rows,
            'documentsDone' => $rows->filter(fn (array $row) => $row['current'] && $row['current']->status === 'Verified')->count(),
            'fee' => $this->portal->feeShare($ticket),
            'timeline' => $this->portal->timeline($ticket),
            'reference' => $this->portal->paymentReference($customer),
            'portal' => $this->portal,
        ]);
    }
}
