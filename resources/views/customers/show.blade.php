@extends('layouts.app')

@section('title', $customer->name . ' — ' . config('app.name', 'Task Management'))

@push('styles')
<style>

    #customer-show-page .client-stat-card {
        min-height: 116px;
        overflow: hidden;
        position: relative;
        color: #fff;
        border: 0;
        border-radius: .85rem;
        box-shadow: 0 5px 16px rgba(16, 27, 61, .15);
    }

    #customer-show-page .client-stat-card::after {
        content: '';
        position: absolute;
        width: 100px;
        height: 100px;
        right: -15px;
        bottom: -48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .1);
        box-shadow: -34px -10px 0 rgba(255, 255, 255, .08);
    }

    #customer-show-page .client-section-title {
        background: #101b3d;
        color: #fff;
        padding: .85rem 1.15rem;
        border-radius: .8rem .8rem 0 0;
    }

    #customer-show-page .client-section-title h2 {
        font-size: 1rem;
    }

    #customer-show-page .client-info-label {
        color: #6b7280;
    }

    #customer-show-page .client-data-row + .client-data-row {
        border-top: 1px solid #edf0f3;
    }

    #customer-show-page .client-tabs .nav-link {
        color: #6b7280;
        border: 0;
        border-bottom: 3px solid transparent;
        font-size: .88rem;
        font-weight: 600;
    }

    #customer-show-page .client-tabs .nav-link.active {
        color: #1f2430;
        background: transparent;
        border-bottom-color: #29995a;
    }

    #customer-show-page .client-ticket-table thead th {
        background: #f5f6f8;
        color: #6b7280;
        border-bottom: 1px solid #e5e7ec;
    }

    #customer-show-page .client-ticket-table thead th:first-child,
    #customer-show-page .client-ticket-table thead th:last-child {
        border-radius: 0;
    }

    #customer-show-page .client-side-heading {
        color: #fff;
        padding: .8rem 1rem;
        border-radius: .8rem .8rem 0 0;
    }

    #customer-show-page .client-summary-row + .client-summary-row {
        border-top: 1px solid #edf0f3;
    }

    #customer-show-page .client-stat-card,
    #customer-show-page .tm-card > .p-3,
    #customer-show-page .client-fee-pending {
        padding-top: .5rem !important;
    }

    #customer-show-page .client-ticket-table thead th {
        padding-top: .6rem;
    }
    #customer-show-page .client-data-row,
    #customer-show-page .client-data-row *,
    #customer-show-page .client-summary-row,
    #customer-show-page .client-summary-row *,
    #customer-show-page .client-fee-pending,
    #customer-show-page .client-fee-pending *,
    #customer-show-page .client-detail-note,
    #customer-show-page .client-detail-note * {
        font-size: .8rem;
    }
    @media (max-width: 575.98px) {
    }
    #customer-show-page .client-since-meta {
        color: #000;
    }

</style>
@endpush

@section('content')
@php
    $tickets = $customer->tickets->sortByDesc('created_at')->values();
    $pendingTickets = $tickets->filter(fn ($ticket) => ! $ticket->status->isClosed())->values();
    $historyTickets = $tickets->filter(fn ($ticket) => $ticket->status->isClosed())->values();
    $totalBilled = $tickets->sum('total');
    $enquiries = $tickets->pluck('enquiry')->filter()->unique('id')->sortByDesc('created_at')->values();
    $feesReceived = $enquiries->sum(fn ($enquiry) => $enquiry->amountPaid());
    $feesPending = $enquiries->sum(fn ($enquiry) => $enquiry->balanceDue());
    $portalLabels = [
        'activated' => ['label' => 'Activated', 'class' => 'text-bg-success'],
        'invite_sent' => ['label' => 'Invite sent', 'class' => 'text-bg-primary'],
        'not_logged_in' => ['label' => 'Not logged in yet', 'class' => 'text-bg-warning'],
    ];
    $portalInfo = $portalLabels[$customer->portal_status] ?? ['label' => ucwords(str_replace('_', ' ', (string) $customer->portal_status)), 'class' => 'text-bg-secondary'];
@endphp

<div id="customer-show-page">
    <x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Clients', 'url' => route('customers.index')], ['label' => $customer->name]]" />
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-3 tm-divider-gold">
        <div class="d-flex align-items-center gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="tm-serif fw-bold mb-0" style="font-size: 1.15rem;">{{ $customer->name }}</h1>
                    <span class="badge rounded-pill {{ $customer->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="tm-muted client-since-meta" style="font-size: .8rem;">
                    Client since {{ $customer->created_at?->format('M Y') ?? '—' }}
                    <span class="mx-1">·</span>
                    Added by {{ $customer->createdBy?->name ?? '—' }}
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">&larr; Back to Clients</a>
            <a href="{{ route('enquiries.create') }}" class="btn btn-outline-success">+ New enquiry</a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-tm-primary">Edit client</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3" style="background: linear-gradient(135deg, #102b68, #2865d5);">
                <div class="position-relative" style="z-index: 1;">
                    <div class="small mb-2">Open tickets</div>
                    <div class="h3 fw-bold mb-1">{{ $pendingTickets->count() }}</div>
                    <div class="small opacity-75">in progress</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3" style="background: linear-gradient(135deg, #0c4222, #228347);">
                <div class="position-relative" style="z-index: 1;">
                    <div class="small mb-2">Completed</div>
                    <div class="h3 fw-bold mb-1">{{ $historyTickets->count() }}</div>
                    <div class="small opacity-75">closed tickets</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3" style="background: linear-gradient(135deg, #07554f, #17857a);">
                <div class="position-relative" style="z-index: 1;">
                    <div class="small mb-2">Fees paid</div>
                    <div class="h3 fw-bold mb-1">₹{{ number_format((float) $feesReceived) }}</div>
                    <div class="small opacity-75">final payment received</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3" style="background: linear-gradient(135deg, #5d1117, #981f27);">
                <div class="position-relative" style="z-index: 1;">
                    <div class="small mb-2">Fees pending</div>
                    <div class="h3 fw-bold mb-1">₹{{ number_format((float) $feesPending) }}</div>
                    <div class="small opacity-75">not fully paid</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-section-title"><h2 class="mb-0 fw-bold">Contact details</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="row g-0">
                        <div class="col-12 col-md-6 pe-md-4">
                            <div class="client-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="client-info-label">Full name</span><strong class="text-end">{{ $customer->name }}</strong>
                            </div>
                            <div class="client-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="client-info-label">Mobile</span><strong class="text-end">{{ $customer->phone }}</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 ps-md-4">
                            <div class="client-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="client-info-label">Status</span><strong class="text-end {{ $customer->is_active ? 'text-success' : 'text-secondary' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</strong>
                            </div>
                            <div class="client-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="client-info-label">Email</span><strong class="text-end text-break">{{ $customer->email ?: '—' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-section-title"><h2 class="mb-0 fw-bold">Pending Tickets</h2></div>
                <div class="table-responsive">
                    <table class="table tm-table client-ticket-table align-middle mb-0">
                        <thead><tr><th>Ticket</th><th>Assigned to</th><th>Status</th><th class="text-end">Fee</th></tr></thead>
                        <tbody>
                            @forelse ($pendingTickets as $ticket)
                                <tr>
                                    <td><a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none">{{ $ticket->number }}</a><div class="tm-muted small">{{ $ticket->service->name }}</div></td>
                                    <td>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                                    <td><x-status-badge :status="$ticket->status->value" /></td>
                                    <td class="text-end fw-semibold">₹{{ number_format((float) $ticket->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center tm-muted py-4">No pending tickets.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="client-section-title"><h2 class="mb-0 fw-bold">Enquiries ({{ $enquiries->count() }})</h2></div>
                <div class="p-3 px-lg-4">
                    @forelse ($enquiries as $enquiry)
                        @php
                            $enquiryTickets = $tickets->where('enquiry_id', $enquiry->id);
                            $serviceNames = $enquiryTickets->pluck('service.name')->filter()->unique()->implode(', ');
                        @endphp
                        <div class="client-summary-row d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
                            <div>
                                <a href="{{ route('enquiries.show', $enquiry) }}" class="fw-bold text-decoration-none">{{ $enquiry->number }}</a>
                                <span class="tm-muted small">· {{ $enquiry->created_at->format('j M Y') }}</span>
                                <div class="tm-muted small mt-1">{{ $serviceNames ?: 'No services listed' }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <strong>₹{{ number_format((float) $enquiry->total) }}</strong>
                                <span class="badge rounded-pill {{ $enquiry->status === 'Open' ? 'text-bg-primary' : 'text-bg-success' }}">{{ $enquiry->status }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center tm-muted py-4">No enquiries recorded for this client.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-side-heading" style="background: #1f6b30;"><h2 class="h6 fw-bold mb-0">Fees summary</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Total billed</span><strong>₹{{ number_format((float) $totalBilled) }}</strong></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Received</span><strong class="text-success">₹{{ number_format((float) $feesReceived) }}</strong></div>
                    <div class="client-fee-pending mt-3 p-3 rounded d-flex justify-content-between align-items-center gap-2" style="background: #fceaea; color: #8f2020;"><div><strong>Pending</strong><div class="small">Across unpaid tickets</div></div><strong class="fs-5">₹{{ number_format((float) $feesPending) }}</strong></div>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-side-heading" style="background: #101b3d;"><h2 class="h6 fw-bold mb-0">Portal access</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-summary-row d-flex justify-content-between align-items-center gap-3 py-3"><span class="client-info-label">Status</span><span class="badge rounded-pill {{ $portalInfo['class'] }}">{{ $portalInfo['label'] }}</span></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Login email</span><strong class="text-end text-break">{{ $customer->email ?: '—' }}</strong></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Last login</span><span class="tm-muted text-end">Not recorded</span></div>
                    <div class="client-detail-note small tm-muted border-top pt-3">Portal login history and resend links are not available yet.</div>
                </div>
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="client-side-heading" style="background: #1f6b30;"><h2 class="h6 fw-bold mb-0">Documents needing action</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-detail-note tm-muted small">Document review status is not available yet.</div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
