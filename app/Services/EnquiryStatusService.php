<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Enquiry;
use App\Models\Ticket;

class EnquiryStatusService
{
    /**
     * Close an enquiry once every one of its tickets is Task Completed and
     * the enquiry has been paid in full — reopen it if either stops being
     * true (e.g. a payment is edited/deleted back below the balance due).
     */
    public static function recompute(Enquiry $enquiry): void
    {
        $enquiry->load(['tickets', 'payments']);

        $shouldBeClosed = $enquiry->tickets->isNotEmpty()
            && $enquiry->tickets->every(fn (Ticket $ticket): bool => $ticket->status->isClosed())
            && $enquiry->paymentStatus() === PaymentStatus::FullyPaid;

        $newStatus = $shouldBeClosed ? 'Closed' : 'Open';

        if ($newStatus === $enquiry->status) {
            return;
        }

        $before = $enquiry->status;
        $enquiry->update(['status' => $newStatus]);

        AuditLogger::log(
            action: 'Updated',
            module: 'Enquiry',
            recordLabel: $enquiry->number,
            recordUrl: route('enquiries.show', $enquiry),
            details: "Status {$before} → {$newStatus}",
            subject: $enquiry,
        );
    }
}
