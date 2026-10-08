<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function data(array $filters, User $user): array
    {
        $tickets = $this->ticketQuery($filters, $user)->with([
            'customer', 'service', 'assignedTo', 'enquiry.payments', 'documents',
        ])->get();

        $payments = Payment::query()
            ->with(['enquiry.customer', 'enquiry.tickets.service', 'receivedBy'])
            ->whereHas('enquiry.tickets', fn (Builder $query) => $this->scopeTicketQuery($query, array_diff_key($filters, ['from' => true, 'to' => true]), $user))
            ->when($filters['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('paid_at', '<=', $date))
            ->when($filters['mode'] ?? null, fn (Builder $query, string $mode) => $query->where('mode', $mode))
            ->when($filters['received_by'] ?? null, fn (Builder $query, int $id) => $query->where('received_by', $id))
            ->latest('paid_at')->get();

        $services = Service::query()->orderBy('name')->get();
        $staff = User::query()->where('role', UserRole::Employee->value)->when($user->isEmployee(), fn (Builder $query) => $query->whereKey($user->id))->orderBy('name')->get();
        $clients = Customer::query()->when($user->isEmployee(), fn (Builder $query) => $query->whereHas('tickets', fn (Builder $ticketsQuery) => $ticketsQuery->where('assigned_to', $user->id)))->orderBy('name')->get();
        $selectedClient = isset($filters['client_id'])
            ? $clients->firstWhere('id', (int) $filters['client_id'])
            : null;

        // Payment is tracked per enquiry (which may cover several tickets/services
        // sharing a discount), not per ticket. Figures below dedupe by enquiry
        // before summing so a multi-ticket enquiry isn't counted more than once;
        // when a service/staff filter narrows an enquiry down to a subset of its
        // tickets, its full enquiry-level received/pending amount still applies
        // to that subset, since a payment isn't attributable to one service alone.
        $paid = (float) $payments->sum('amount');
        $gstPaid = (float) $payments->sum(fn (Payment $payment): float => $payment->enquiry && (float) $payment->enquiry->total > 0 ? (float) $payment->amount * ((float) $payment->enquiry->gst_total / (float) $payment->enquiry->total) : 0);

        $uniqueEnquiries = $tickets->pluck('enquiry')->filter()->unique('id');
        $gstPending = (float) $uniqueEnquiries->sum(fn (Enquiry $enquiry): float => (float) $enquiry->total > 0 ? (float) $enquiry->gst_total * ($enquiry->balanceDue() / (float) $enquiry->total) : 0);
        $billed = (float) $tickets->sum('total');
        $pending = max(0, $billed - (float) $uniqueEnquiries->sum(fn (Enquiry $enquiry): float => $enquiry->amountPaid()));
        $serviceBreakdown = $tickets->groupBy('service_id')->map(function (Collection $rows): array {
            $rowEnquiries = $rows->pluck('enquiry')->filter()->unique('id');

            return [
                'service' => $rows->first()->service?->name ?? 'Unassigned service',
                'tickets' => $rows->count(),
                'billed' => (float) $rows->sum('total'),
                'received' => (float) $rowEnquiries->sum(fn (Enquiry $enquiry) => $enquiry->amountPaid()),
                'pending' => (float) $rowEnquiries->sum(fn (Enquiry $enquiry) => $enquiry->balanceDue()),
                'gst' => (float) $rows->sum('gst_amount'),
                'service_id' => $rows->first()->service_id,
            ];
        })->values();

        $statusCounts = collect(TicketStatus::cases())->mapWithKeys(fn (TicketStatus $status): array => [
            $status->value => $tickets->filter(fn (Ticket $ticket): bool => $ticket->status?->value === $status->value)->count(),
        ]);
        $openTickets = $tickets->filter(fn (Ticket $ticket): bool => ! $ticket->status?->isClosed())
            ->sortBy(fn (Ticket $ticket) => $ticket->created_at?->timestamp ?? 0)->values();
        $pendingDocuments = $tickets->flatMap(fn (Ticket $ticket) => $ticket->documents
            ->where('status', 'Awaiting check')->map(fn ($document) => ['ticket' => $ticket, 'document' => $document]))
            ->values();

        $clientTickets = $selectedClient
            ? $tickets->where('customer_id', $selectedClient->id)->values()
            : collect();

        $months = collect(range(0, 5))->map(function (int $offset) use ($payments, $tickets): array {
            $month = now()->startOfMonth()->subMonths(5 - $offset);

            return [
                'label' => $month->format('M'),
                'billed' => (float) $tickets->filter(fn (Ticket $ticket): bool => $ticket->created_at?->format('Y-m') === $month->format('Y-m'))->sum('total'),
                'received' => (float) $payments->filter(fn (Payment $payment): bool => $payment->paid_at?->format('Y-m') === $month->format('Y-m'))->sum('amount'),
            ];
        });

        return compact(
            'tickets', 'payments', 'services', 'staff', 'clients', 'selectedClient',
            'paid', 'gstPaid', 'gstPending', 'billed', 'pending', 'serviceBreakdown', 'statusCounts',
            'openTickets', 'pendingDocuments', 'clientTickets', 'months'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ticket>
     */
    public function tickets(array $filters, User $user): Collection
    {
        return $this->ticketQuery($filters, $user)
            ->with(['customer', 'service', 'assignedTo', 'enquiry.payments'])
            ->latest()->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function ticketQuery(array $filters, User $user): Builder
    {
        return $this->scopeTicketQuery(Ticket::query(), $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function scopeTicketQuery(Builder $query, array $filters, User $user): Builder
    {
        return $query
            ->when($user->isEmployee(), fn (Builder $builder) => $builder->where('assigned_to', $user->id))
            ->when($filters['from'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('created_at', '<=', $date))
            ->when($filters['service_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('service_id', $id))
            ->when($filters['staff_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('assigned_to', $id))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['client_id'] ?? null, fn (Builder $builder, int $id) => $builder->where('customer_id', $id));
    }
}
