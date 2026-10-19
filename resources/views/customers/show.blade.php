@extends('layouts.app')

@section('title', $customer->name . ' — ' . config('app.name', 'Task Management'))


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
                    <h1 class="tm-serif fw-bold mb-0 u-e83e11">{{ $customer->name }}</h1>
                    <span class="badge rounded-pill {{ $customer->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="tm-muted client-since-meta u-5a305f">
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
            <div class="tm-card client-stat-card p-3 u-c34d0f">
                <div class="position-relative u-587f46">
                    <div class="small mb-2">Open services</div>
                    <div class="h3 fw-bold mb-1">{{ $pendingTickets->count() }}</div>
                    <div class="small opacity-75 u-31578e">in progress</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3 u-e30e5d">
                <div class="position-relative u-587f46">
                    <div class="small mb-2">Completed</div>
                    <div class="h3 fw-bold mb-1">{{ $historyTickets->count() }}</div>
                    <div class="small opacity-75 u-31578e">closed services</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3 u-42d223">
                <div class="position-relative u-587f46">
                    <div class="small mb-2">Fees paid</div>
                    <div class="h3 fw-bold mb-1">₹{{ number_format((float) $feesReceived) }}</div>
                    <div class="small opacity-75 u-31578e">final payment received</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card client-stat-card p-3 u-524b25">
                <div class="position-relative u-587f46">
                    <div class="small mb-2">Fees pending</div>
                    <div class="h3 fw-bold mb-1">₹{{ number_format((float) $feesPending) }}</div>
                    <div class="small opacity-75 u-31578e">not fully paid</div>
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
                <div class="client-section-title"><h2 class="mb-0 fw-bold">Pending Services</h2></div>
                <div class="table-responsive">
                    <table class="table tm-table client-ticket-table align-middle mb-0">
                        <thead><tr><th>Service</th><th>Assigned to</th><th>Status</th><th class="text-end">Fee</th></tr></thead>
                        <tbody>
                            @forelse ($pendingTickets as $ticket)
                                <tr>
                                    <td><a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none">{{ $ticket->number }}</a><div class="tm-muted small">{{ $ticket->service->name }}</div></td>
                                    <td>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                                    <td><x-status-badge :status="$ticket->status->value" /></td>
                                    <td class="text-end fw-semibold">₹{{ number_format((float) $ticket->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center tm-muted py-4 u-5a305f">No pending services.</td></tr>
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
                        <div class="text-center tm-muted py-4 u-5a305f">No enquiries recorded for this client.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-side-heading u-1fcbae"><h2 class="h6 fw-bold mb-0">Fees summary</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Total billed</span><strong>₹{{ number_format((float) $totalBilled) }}</strong></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Received</span><strong class="text-success">₹{{ number_format((float) $feesReceived) }}</strong></div>
                    <div class="client-fee-pending mt-3 p-3 rounded d-flex justify-content-between align-items-center gap-2 u-2253be"><div><strong>Pending</strong><div class="small">Across unpaid services</div></div><strong class="fs-5">₹{{ number_format((float) $feesPending) }}</strong></div>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="client-side-heading u-8b012c"><h2 class="h6 fw-bold mb-0">Portal access</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-summary-row d-flex justify-content-between align-items-center gap-3 py-3"><span class="client-info-label">Status</span><span class="badge rounded-pill {{ $portalInfo['class'] }}">{{ $portalInfo['label'] }}</span></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Login email</span><strong class="text-end text-break">{{ $customer->email ?: '—' }}</strong></div>
                    <div class="client-summary-row d-flex justify-content-between gap-3 py-3"><span class="client-info-label">Last login</span><span class="tm-muted text-end">Not recorded</span></div>
                    <div class="client-detail-note small tm-muted border-top pt-3">Portal login history and resend links are not available yet.</div>
                </div>
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="client-side-heading u-1fcbae"><h2 class="h6 fw-bold mb-0">Documents needing action</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="client-detail-note tm-muted small">Document review status is not available yet.</div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
