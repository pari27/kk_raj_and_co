<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\ClientNotification;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What a client sees in the portal. Every query starts from the client's own
 * services, so nothing belonging to anyone else can come back.
 */
class ClientPortalService
{
    /**
     * The steps a client sees, whatever the internal status is.
     */
    public const STEPS = ['Documents', 'Checking', 'Work in progress', 'Completed'];

    /**
     * Services of this client, newest first.
     *
     * @return Collection<int, Ticket>
     */
    public function services(Customer $customer): Collection
    {
        return Ticket::query()
            ->where('customer_id', $customer->id)
            ->with(['service.documents', 'documents', 'enquiry.payments', 'enquiry.tickets', 'assignedTo.profile.designation'])
            ->latest('created_at')->latest('id')
            ->get();
    }

    /**
     * Which of the four work steps a status belongs to. Payment is not a step: it can happen at any stage.
     */
    public static function stepFor(TicketStatus $status): int
    {
        return match ($status) {
            TicketStatus::DocumentsPending, TicketStatus::PartiallyReceived, TicketStatus::AdditionalDocumentsRequired => 1,
            TicketStatus::DocumentsReceived, TicketStatus::DocumentsVerified => 2,
            TicketStatus::TaskCompleted => 4,
            default => 3,
        };
    }

    /**
     * Where this service stands, in the client's words. `step` runs 1 to 4; payment is
     * reported separately in `paid`.
     *
     * @return array{step: int, label: string, tone: string, caption: string, paid: bool}
     */
    public function stage(Ticket $ticket): array
    {
        $rows = $this->documentRows($ticket);
        $sent = $rows->filter(fn (array $row) => $row['current'] && $row['current']->status !== 'Rejected')->count();
        $step = self::stepFor($ticket->status);
        $paid = $this->feeShare($ticket)['balance'] <= 0;

        $stage = match ($step) {
            1 => ['label' => 'Documents needed', 'tone' => 'amber', 'caption' => $rows->isEmpty() ? 'Waiting for your documents' : $sent.' of '.$rows->count().' documents uploaded'],
            2 => ['label' => 'Checking', 'tone' => 'blue', 'caption' => 'We are checking your documents'],
            4 => ['label' => 'Completed', 'tone' => 'green', 'caption' => $ticket->completed_at ? 'Completed on '.$ticket->completed_at->format('j M Y') : 'Completed'],
            default => [
                'label' => $ticket->status === TicketStatus::OnHold ? 'On hold' : 'Work in progress',
                'tone' => 'yellow',
                'caption' => $ticket->status === TicketStatus::OnHold ? 'We will tell you when it moves again' : 'Our team is working on it',
            ],
        };

        return $stage + [
            'step' => $step,
            'paid' => $paid,
        ];
    }

    /**
     * The checklist for one service: each document with its latest upload.
     *
     * @return Collection<int, array{serviceDocument: \App\Models\ServiceDocument, current: \App\Models\TicketDocument|null, versions: Collection, needsUpload: bool}>
     */
    public function documentRows(Ticket $ticket): Collection
    {
        return $ticket->service->documents->map(function ($serviceDocument) use ($ticket) {
            $versions = $ticket->documents
                ->where('service_document_id', $serviceDocument->id)
                ->sortByDesc('version')
                ->values();

            $current = $versions->first();

            return [
                'serviceDocument' => $serviceDocument,
                'current' => $current,
                'versions' => $versions,
                // Missing, or the last one was rejected: the client has to (re)send it.
                'needsUpload' => ! $ticket->status->isClosed() && (! $current || $current->status === 'Rejected'),
            ];
        });
    }

    /**
     * How many of this service's documents the client has sent (and not had rejected).
     *
     * @return array{sent: int, total: int}
     */
    public function documentCounts(Ticket $ticket): array
    {
        $rows = $this->documentRows($ticket);

        return [
            'sent' => $rows->filter(fn (array $row) => $row['current'] && $row['current']->status !== 'Rejected')->count(),
            'total' => $rows->count(),
        ];
    }

    /**
     * How many documents are still waiting on this client.
     */
    public function documentsPending(Collection $services): int
    {
        return $services
            ->filter(fn (Ticket $ticket) => ! $ticket->status->isClosed())
            ->sum(fn (Ticket $ticket) => $this->documentRows($ticket)->where('needsUpload', true)->count());
    }

    /**
     * The numbers on the dashboard cards.
     *
     * @return array{active: int, documents: int, documentServices: string, due: float, dueServices: int, completed: int}
     */
    public function stats(Collection $services, array $fees): array
    {
        $needing = $services
            ->filter(fn (Ticket $ticket) => ! $ticket->status->isClosed())
            ->filter(fn (Ticket $ticket) => $this->documentRows($ticket)->where('needsUpload', true)->isNotEmpty());

        return [
            'active' => $services->filter(fn (Ticket $ticket) => ! $ticket->status->isClosed())->count(),
            'documents' => $this->documentsPending($services),
            'documentServices' => $needing->count() === 1
                ? 'for '.$needing->first()->service->name
                : ($needing->count() > 1 ? 'across '.$needing->count().' services' : 'nothing to send'),
            'due' => $fees['pending'],
            'dueServices' => $services->filter(fn (Ticket $ticket) => $this->feeShare($ticket)['balance'] > 0)->count(),
            'completed' => $services->filter(fn (Ticket $ticket) => $ticket->status->isClosed() && $ticket->completed_at?->isCurrentYear())->count(),
        ];
    }

    /**
     * What the client has to do next, most urgent first: documents to re-send,
     * documents to send, then fees.
     *
     * @return Collection<int, array{tone: string, title: string, sub: string, action: string, url: string}>
     */
    public function attentionItems(Collection $services, array $fees): Collection
    {
        $rejected = collect();
        $missing = collect();

        foreach ($services->filter(fn (Ticket $ticket) => ! $ticket->status->isClosed()) as $ticket) {
            foreach ($this->documentRows($ticket)->where('needsUpload', true) as $row) {
                $document = $row['serviceDocument'];
                $url = route('customer.tickets.show', $ticket);

                if ($row['current']) {
                    $rejected->push([
                        'tone' => 'red',
                        'title' => 'Re-upload your '.$document->name,
                        'sub' => $ticket->service->name.' · '.($row['current']->rejection_reason ?: 'it was not accepted'),
                        'action' => 'Re-upload →',
                        'url' => $url,
                    ]);
                } else {
                    $missing->push([
                        'tone' => 'amber',
                        'title' => 'Upload your '.$document->name,
                        'sub' => $ticket->service->name.($document->isText() ? ' · type the details' : ' · '.trim($document->allowed_formats.($document->formattedMaxSize() ? ', up to '.$document->formattedMaxSize() : ''))),
                        'action' => 'Upload →',
                        'url' => $url,
                    ]);
                }
            }
        }

        $items = $rejected->concat($missing);

        if ($fees['pending'] > 0) {
            $items->push([
                'tone' => 'blue',
                'title' => 'Pay ₹'.number_format($fees['pending']),
                'sub' => $fees['services'],
                'action' => 'How to pay →',
                'url' => route('customer.payments.index'),
            ]);
        }

        return $items->take(8)->values();
    }

    /**
     * This service's share of its enquiry's fee, what has been paid towards it, and what is left.
     * A payment belongs to the enquiry, so it is split by each service's share of the enquiry.
     *
     * @return array{billed: float, paid: float, balance: float}
     */
    public function feeShare(Ticket $ticket): array
    {
        $enquiry = $ticket->enquiry;

        if (! $enquiry) {
            return ['billed' => (float) $ticket->total, 'paid' => 0.0, 'balance' => (float) $ticket->total];
        }

        $sum = (float) $enquiry->tickets->sum('total');
        $ratio = $sum > 0 ? (float) $ticket->total / $sum : 0.0;

        $billed = (float) $enquiry->total * $ratio;
        $paid = (float) $enquiry->amountPaid() * $ratio;

        return ['billed' => $billed, 'paid' => $paid, 'balance' => max(0.0, round($billed - $paid, 2))];
    }

    /**
     * A short code the client puts in the payment note so staff can match the payment, e.g. MANOJ-87587.
     */
    public function paymentReference(Customer $customer): string
    {
        $first = strtoupper(\Illuminate\Support\Str::of($customer->name)->before(' ')->ascii()->replaceMatches('/[^A-Za-z0-9]/', '')->toString());
        $tail = $customer->phone ? substr(preg_replace('/\D/', '', $customer->phone), -5) : $customer->id;

        return $first.'-'.$tail;
    }

    /**
     * Fees across the client's enquiries.
     *
     * @return array{billed: float, paid: float, pending: float, services: string}
     */
    public function fees(Collection $services): array
    {
        $enquiries = $services->pluck('enquiry')->filter()->unique('id');

        $billed = (float) $enquiries->sum(fn (Enquiry $enquiry) => (float) $enquiry->total);
        $paid = (float) $enquiries->sum(fn (Enquiry $enquiry) => $enquiry->amountPaid());

        $owing = $services
            ->filter(fn (Ticket $ticket) => $this->feeShare($ticket)['balance'] > 0)
            ->map(fn (Ticket $ticket) => $ticket->service->name)
            ->unique()->values();

        return [
            'billed' => $billed,
            'paid' => $paid,
            'pending' => max(0.0, $billed - $paid),
            'services' => $this->joinNames($owing),
        ];
    }

    /**
     * Payments the client has made, newest first.
     *
     * @return Collection<int, Payment>
     */
    public function payments(Customer $customer): Collection
    {
        return Payment::query()
            ->whereHas('enquiry', fn ($enquiry) => $enquiry->where('customer_id', $customer->id))
            ->with('enquiry.tickets.service')
            ->latest('paid_at')->latest('id')
            ->get();
    }

    /**
     * The staff member who looks after most of the client's open work.
     */
    public function mainContact(Collection $services): ?User
    {
        $id = $services
            ->filter(fn (Ticket $ticket) => ! $ticket->status->isClosed() && $ticket->assignedTo)
            ->countBy(fn (Ticket $ticket) => $ticket->assignedTo->id)
            ->sortDesc()
            ->keys()
            ->first();

        return $id ? $services->first(fn (Ticket $ticket) => $ticket->assignedTo?->id === $id)->assignedTo : null;
    }

    /**
     * How to reach a staff member, as shown on the contact cards.
     *
     * @return array{name: string, initials: string, role: string|null, phone: string|null, email: string|null}
     */
    public function contactCard(User $user): array
    {
        $mobile = preg_replace('/\D/', '', (string) $user->profile?->mobile);

        return [
            'name' => $user->name,
            'initials' => $this->initials($user->name),
            'role' => $user->profile?->designation?->name,
            'phone' => strlen($mobile) === 10 ? substr($mobile, 0, 5).' '.substr($mobile, 5) : ($mobile ?: null),
            'email' => $user->email,
        ];
    }

    /**
     * Progress of one service, in words a client should see: creation, status
     * changes and what happened to their documents. Staff names and internal
     * moves such as reassignments are left out.
     *
     * @return Collection<int, array{title: string, when: \Illuminate\Support\Carbon, tone: string}>
     */
    public function timeline(Ticket $ticket): Collection
    {
        return AuditLog::query()
            ->where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->oldest('created_at')->oldest('id')
            ->get()
            ->filter(function (AuditLog $entry) {
                $details = (string) $entry->details;

                return $entry->action === 'Created'
                    || preg_match('/^(Status |Uploaded |Re-uploaded |Verified |Rejected )/', $details) === 1;
            })
            ->map(function (AuditLog $entry) {
                $title = $this->describe($entry);

                return [
                    'title' => $title,
                    'when' => $entry->created_at,
                    'tone' => match (true) {
                        str_starts_with($title, 'Rejected') => 'red',
                        str_starts_with($title, 'Verified') => 'green',
                        default => 'gray',
                    },
                ];
            })
            ->reverse()
            ->values();
    }

    public function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }

    /**
     * Add a line to a client's Updates feed.
     */
    public static function notify(Customer $customer, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        ClientNotification::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }

    public function unreadUpdates(Customer $customer): int
    {
        return ClientNotification::where('customer_id', $customer->id)->whereNull('read_at')->count();
    }

    /**
     * "A", "A and B", "A, B and C".
     */
    public function joinNames(Collection $names): string
    {
        $names = $names->values();

        return match ($names->count()) {
            0 => '',
            1 => $names[0],
            default => $names->slice(0, -1)->implode(', ').' and '.$names->last(),
        };
    }

    private function describe(AuditLog $entry): string
    {
        $details = (string) $entry->details;

        if ($entry->action === 'Created') {
            return 'Your service was created';
        }

        // "Uploaded PAN Card (v1) on SRV-2026-0001" -> without the internal service number
        return trim(preg_replace('/ on SRV-[A-Z0-9-]+| on [A-Z]{2,6}-\d{4}-\d{4}/', '', $details) ?? $details);
    }
}
