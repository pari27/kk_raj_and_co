@extends('layouts.app')

@section('title', 'Payments — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
<style>
    #paymentsTicketsTable.tm-table thead th:first-child,
    #paymentsTicketsTable.tm-table thead th:last-child,
    #paymentsPendingTable.tm-table thead th:first-child,
    #paymentsPendingTable.tm-table thead th:last-child {
        border-radius: 0;
    }
    #paymentsTicketsTable.tm-table tbody td,
    #paymentsPendingTable.tm-table tbody td {
        font-size: .8rem;
    }
    #paymentsPendingTable .js-record-payment {
        color: var(--tm-navy);
        border-color: var(--tm-navy);
        font-size: .72rem;
        padding: .2rem .6rem;
    }
    #paymentsPendingTable .js-record-payment:hover {
        background: var(--tm-navy);
        border-color: var(--tm-navy);
        color: #fff;
    }
    .daterangepicker td.active, .daterangepicker td.active:hover {
        background-color: var(--tm-accent);
    }
    .daterangepicker .ranges li.active {
        background-color: #101b3d;
        color: #fff;
    }
</style>
@endpush

@section('content')
@php
    $statCards = [
        ['label' => 'Received this month', 'value' => '₹'.number_format((float) $receivedThisMonth->sum('amount')), 'caption' => $receivedThisMonth->count().' payments', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Pending', 'value' => '₹'.number_format($pendingEnquiries->sum(fn ($e) => $e->balanceDue())), 'caption' => 'across '.$pendingEnquiries->count().' enquiries', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
        ['label' => 'Received this FY', 'value' => '₹'.number_format((float) $receivedThisFy), 'caption' => 'April to '.now()->format('F'), 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Partial payments', 'value' => (string) $partialPaymentsCount, 'caption' => 'balance still due', 'gradient' => 'linear-gradient(135deg, #3a2208, #8a5a16)'],
    ];

    $modeColors = ['UPI' => '#6d5bd0', 'Bank transfer' => '#2f5fbe', 'Cash' => '#1f6b30', 'Cheque' => '#b9650a'];
    $isAdminLike = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();

    $paymentEnquiriesData = $pendingEnquiries->mapWithKeys(fn ($enquiry) => [
        $enquiry->id => [
            'client' => $enquiry->customer->name ?? '—',
            'enquiry' => $enquiry->number.' · '.$enquiry->tickets->pluck('service.name')->filter()->implode(', '),
            'total' => (float) $enquiry->total,
            'paid' => $enquiry->amountPaid(),
            'balance' => $enquiry->balanceDue(),
            'ticketCount' => $enquiry->tickets->count(),
            'allClosed' => $enquiry->tickets->isNotEmpty() && $enquiry->tickets->every(fn ($t) => $t->status->isClosed()),
        ],
    ]);
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Payments']]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="tm-serif fw-bold mb-1" style="font-size: 1.15rem;">Payments</h1>
        <p class="tm-muted mb-0" style="font-size: .8rem;">Fees received and still pending across all enquiries</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('payments.export') }}" class="btn btn-outline-secondary">&darr; Export</a>
        <button type="button" class="btn btn-tm-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">+ Record payment</button>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['value'] }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0 mb-3">
    <div class="d-flex align-items-center gap-4 px-3 pt-3">
        <button type="button" class="tm-ticket-tab active" data-tab="pending">
            Pending <span class="badge rounded-pill text-bg-light border ms-1">{{ $pendingEnquiries->count() }}</span>
        </button>
        <button type="button" class="tm-ticket-tab" data-tab="received">
            Received <span class="badge rounded-pill text-bg-light border ms-1">{{ $payments->count() }}</span>
        </button>
        
    </div>
    <hr class="mt-2 mb-0">

    <div class="p-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="tm-search d-flex align-items-center gap-2 px-3 py-2" style="max-width: 320px; background: #fff; border: 1px solid var(--tm-surface-border);">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="paymentsSearch" placeholder="Search client, enquiry or reference" style="background: transparent; border: 0; outline: none; color: var(--tm-text); width: 100%; font-size: .85rem;">
        </div>

        <div class="d-flex flex-wrap gap-2">
            <div style="min-width: 180px;">
                <button type="button" id="paymentDateRangeBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span id="paymentDateRangeLabel" class="flex-grow-1 text-start" style="font-weight: 400;">All time</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <input type="hidden" id="paymentDateFrom">
                <input type="hidden" id="paymentDateTo">
            </div>

            <div id="receivedFilters" class="d-flex flex-wrap gap-2 d-none">
                <select id="modeFilter" class="form-select form-select-sm" style="width: auto; font-size: .8rem;">
                    <option value="">Mode: All</option>
                    <option value="UPI">UPI</option>
                    <option value="Bank transfer">Bank transfer</option>
                    <option value="Cash">Cash</option>
                    <option value="Cheque">Cheque</option>
                </select>
                <select id="receivedByFilter" class="form-select form-select-sm" style="width: auto; font-size: .8rem;">
                    <option value="">Received by: All</option>
                    @foreach ($receivedByUsers as $user)
                        <option value="{{ $user->name }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="receivedTable" class="table-responsive d-none">
        <table id="paymentsTicketsTable" class="table tm-table align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Enquiry</th>
                    <th class="text-end">Amount</th>
                    <th>Mode</th>
                    <th>Reference</th>
                    <th>Received by</th>
                    <th>Payment</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $enquiry = $payment->enquiry;
                        $services = $enquiry?->tickets->pluck('service.name')->filter()->implode(', ');
                    @endphp
                    <tr
                        data-client="{{ strtolower($enquiry?->customer->name ?? '') }}"
                        data-ticket="{{ strtolower($enquiry->number ?? '') }}"
                        data-service="{{ strtolower($services ?? '') }}"
                        data-reference="{{ strtolower($payment->reference ?? '') }}"
                        data-amount="{{ (int) $payment->amount }}"
                        data-mode="{{ $payment->mode }}"
                        data-received-by="{{ $payment->receivedBy->name ?? '' }}"
                        data-phone="{{ strtolower($enquiry?->customer->phone ?? '') }}"
                        data-date="{{ strtolower($payment->paid_at->format('j M Y')) }}"
                        data-date-ts="{{ $payment->paid_at->timestamp }}"
                        data-payment-type="{{ $payment->is_partial ? 'partial' : 'full' }}"
                    >
                        <td class="fw-semibold">{{ $payment->paid_at->format('j M Y') }}</td>
                        <td>
                            @if ($enquiry?->customer)
                                <a href="{{ route('customers.show', $enquiry->customer) }}" class="fw-semibold text-decoration-none">{{ $enquiry->customer->name }}</a>
                            @else
                                <div class="fw-semibold">—</div>
                            @endif
                            <div class="tm-muted" style="font-size: .75rem;">{{ $enquiry?->customer->phone ?? '' }}</div>
                        </td>
                        <td>
                            <a href="{{ route('enquiries.show', $enquiry) }}" class="fw-semibold text-decoration-none">{{ $enquiry->number ?? '—' }}</a>
                            <div class="tm-muted" style="font-size: .75rem;">{{ $services ?: '' }}</div>
                        </td>
                        <td class="text-end fw-semibold" style="color: #1f6b30;">₹{{ number_format((float) $payment->amount) }}</td>
                        <td>
                            <span class="badge rounded-pill fw-normal" style="background: #eef4ff; color: {{ $modeColors[$payment->mode] ?? '#2f5fbe' }};">{{ $payment->mode }}</span>
                        </td>
                        <td class="tm-muted">{{ $payment->reference ?: '—' }}</td>
                        <td>{{ $payment->receivedBy->name ?? '—' }}</td>
                        <td>
                            <span class="d-flex align-items-center gap-1" style="font-size: .8rem;">
                                <span class="rounded-circle d-inline-block" style="width:7px;height:7px;background:{{ $payment->is_partial ? '#b9650a' : '#1f6b30' }};"></span>
                                {{ $payment->is_partial ? 'Partial' : 'Full' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @php
                                    $balanceExcludingThis = $enquiry
                                        ? (float) $enquiry->total - $enquiry->payments->where('id', '!=', $payment->id)->sum('amount')
                                        : 0;
                                @endphp
                                <button
                                    type="button"
                                    class="tm-icon-btn-sm js-edit-payment"
                                    title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPaymentModal"
                                    data-action="{{ route('payments.update', $payment) }}"
                                    data-client="{{ $enquiry?->customer->name ?? '—' }}"
                                    data-ticket="{{ $enquiry->number ?? '' }} · {{ $services }}"
                                    data-total="{{ (float) ($enquiry->total ?? 0) }}"
                                    data-paid-excluding="{{ (float) ($enquiry->total ?? 0) - $balanceExcludingThis }}"
                                    data-amount="{{ (float) $payment->amount }}"
                                    data-mode="{{ $payment->mode }}"
                                    data-reference="{{ $payment->reference }}"
                                    data-note="{{ $payment->note }}"
                                    data-paid-at="{{ $payment->paid_at->format('Y-m-d') }}"
                                    data-balance="{{ $balanceExcludingThis }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path></svg>
                                </button>
                                @if ($isAdminLike)
                                    <button
                                        type="button"
                                        class="tm-icon-btn-sm js-delete-payment"
                                        title="Delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deletePaymentModal"
                                        data-action="{{ route('payments.destroy', $payment) }}"
                                        data-ticket-label="{{ $enquiry->number ?? '' }} — ₹{{ number_format((float) $payment->amount) }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-empty-state title="No payments recorded yet" description="Payments you record against an enquiry will show up here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="pendingTable" class="table-responsive">
        <table id="paymentsPendingTable" class="table tm-table align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Enquiry</th>
                    <th>Client</th>
                    <th>Services</th>
                    <th class="text-end">Balance due</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingEnquiries as $enquiry)
                    @php
                        $pendingServices = $enquiry->tickets->pluck('service.name')->filter()->implode(', ');
                    @endphp
                    <tr
                        data-client="{{ strtolower($enquiry->customer->name ?? '') }}"
                        data-ticket="{{ strtolower($enquiry->number) }}"
                        data-service="{{ strtolower($pendingServices) }}"
                        data-reference=""
                        data-mode=""
                        data-received-by=""
                        data-phone="{{ strtolower($enquiry->customer->phone ?? '') }}"
                        data-amount="{{ (int) $enquiry->balanceDue() }}"
                        data-date-ts="{{ $enquiry->created_at->timestamp }}"
                    >
                        <td>
                            <a href="{{ route('enquiries.show', $enquiry) }}" class="fw-semibold text-decoration-none">{{ $enquiry->number }}</a>
                            <div class="tm-muted" style="font-size: .72rem;">{{ $enquiry->created_at->format('j M Y') }}</div>
                        </td>
                        <td>
                            @if ($enquiry->customer)
                                <a href="{{ route('customers.show', $enquiry->customer) }}" class="fw-semibold text-decoration-none">{{ $enquiry->customer->name }}</a>
                            @else
                                <div class="fw-semibold">—</div>
                            @endif
                            <div class="tm-muted" style="font-size: .75rem;">{{ $enquiry->customer->phone ?? '' }}</div>
                        </td>
                        <td>{{ $pendingServices ?: '—' }}</td>
                        <td class="text-end fw-semibold" style="color: #7f1616;">₹{{ number_format($enquiry->balanceDue()) }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm js-record-payment"
                                data-bs-toggle="modal"
                                data-bs-target="#recordPaymentModal"
                                data-enquiry-id="{{ $enquiry->id }}"
                                data-enquiry-label="{{ $enquiry->number }} — {{ $enquiry->customer->name ?? '' }}"
                                data-balance="{{ $enquiry->balanceDue() }}"
                            >Record</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state title="Nothing pending" description="Every enquiry has been paid in full." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="tm-card p-0" style="overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between p-3" style="background: #1f6b30;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Received by payment mode</h2>
                <span class="small" style="color: rgba(255,255,255,.75);">{{ now()->format('F') }}</span>
            </div>
            <div class="p-3">
                @if ($modeBreakdown->isEmpty())
                    <p class="tm-muted small mb-0">No payments received this month yet.</p>
                @else
                    <div class="rounded-pill mb-3 d-flex" style="height: 8px; overflow: hidden; background: #eceef2;">
                        @foreach ($modeBreakdown as $mode => $amount)
                            <span style="width: {{ round($amount / $modeTotal * 100) }}%; background: {{ $modeColors[$mode] ?? '#9aa1b0' }};"></span>
                        @endforeach
                    </div>
                    <ul class="list-unstyled mb-0" style="font-size: .85rem;">
                        @foreach ($modeBreakdown as $mode => $amount)
                            <li class="d-flex align-items-center justify-content-between py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background: {{ $modeColors[$mode] ?? '#9aa1b0' }};"></span>
                                    {{ $mode }}
                                </span>
                                <span class="d-flex align-items-center gap-3">
                                    <strong>₹{{ number_format($amount) }}</strong>
                                    <span class="tm-muted" style="width: 2.5rem; text-align: right;">{{ round($amount / $modeTotal * 100) }}%</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="tm-card p-0" style="overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between p-3" style="background: #7f1616;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Largest pending fees</h2>
                <span class="small text-decoration-underline" style="color: #fff; cursor: pointer;" onclick="document.querySelector('[data-tab=pending]').click();">All {{ $pendingEnquiries->count() }} &rarr;</span>
            </div>
            <div class="p-3">
                @forelse ($pendingEnquiries->take(4) as $enquiry)
                    <div class="d-flex align-items-center justify-content-between gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-bold" style="font-size: .8rem;">{{ $enquiry->customer->name ?? '—' }}</div>
                            <div class="tm-muted" style="font-size: .75rem;">{{ $enquiry->number }} &middot; {{ $enquiry->tickets->pluck('service.name')->filter()->implode(', ') }}</div>
                            <div style="font-size: .72rem; color: {{ $enquiry->amountPaid() > 0 ? '#b9650a' : '#7f1616' }};">
                                {{ $enquiry->amountPaid() > 0
                                    ? 'Part paid, ₹'.number_format($enquiry->balanceDue()).' balance'
                                    : $enquiry->tickets->count().' ticket(s)' }}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <strong style="color: #7f1616; font-size: .8rem;">₹{{ number_format($enquiry->balanceDue()) }}</strong>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success js-record-payment"
                                data-bs-toggle="modal"
                                data-bs-target="#recordPaymentModal"
                                data-enquiry-id="{{ $enquiry->id }}"
                                data-enquiry-label="{{ $enquiry->number }} — {{ $enquiry->customer->name ?? '' }}"
                                data-balance="{{ $enquiry->balanceDue() }}"
                            >Record</button>
                        </div>
                    </div>
                @empty
                    <p class="tm-muted small mb-0">Nothing pending right now.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <form method="POST" action="{{ route('payments.store') }}">
                @csrf
                <input type="hidden" name="enquiry_id" id="paymentEnquiryIdInput" value="{{ old('enquiry_id') }}">
                <input type="hidden" name="mode" id="paymentModeInput" value="{{ old('mode', 'UPI') }}">

                <div class="d-flex align-items-start gap-3 p-3" style="background: #101b3d;">
                    <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,.12); color: #fff;">&#8377;</span>
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-0 text-white">Record payment</h2>
                        <div class="small" id="recordPaymentSubtitle" style="color: rgba(255,255,255,.7);">Advance, partial or full — against any enquiry with a balance due</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div id="paymentEnquiryPicker" class="mb-3">
                        <label class="tm-field-label d-block">Enquiry <span class="text-danger">*</span></label>
                        <select id="paymentEnquirySelect" class="form-select tm-field @error('enquiry_id') is-invalid @enderror">
                            <option value="">Select an enquiry</option>
                            @foreach ($pendingEnquiries as $enquiry)
                                <option value="{{ $enquiry->id }}" {{ (string) old('enquiry_id') === (string) $enquiry->id ? 'selected' : '' }}>
                                    {{ $enquiry->number }} — {{ $enquiry->customer->name ?? '' }} (₹{{ number_format($enquiry->balanceDue()) }} due)
                                </option>
                            @endforeach
                        </select>
                        @error('enquiry_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div id="paymentEnquiryCard" class="rounded-3 p-3 mb-3 d-none" style="background: #eef9ef; border: 1px solid #cdeccb;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fw-bold" id="paymentCardClient"></div>
                            <span class="badge rounded-pill fw-normal" id="paymentCardStatus" style="font-size: .68rem;"></span>
                        </div>
                        <div class="mb-2" style="font-size: .82rem; color: #2f5fbe; font-weight: 600;" id="paymentCardTicket"></div>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fff;">
                                    <div class="tm-muted" style="font-size: .68rem;">Total fee</div>
                                    <div class="fw-bold" style="font-size: .85rem;" id="paymentCardTotal"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fff;">
                                    <div class="tm-muted" style="font-size: .68rem;">Already paid</div>
                                    <div class="fw-bold" style="font-size: .85rem;" id="paymentCardPaid"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fceaea;">
                                    <div style="font-size: .68rem; color: #7f1616;">Balance due</div>
                                    <div class="fw-bold" style="font-size: .85rem; color: #7f1616;" id="paymentCardBalance"></div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm p-0 js-change-ticket">Change enquiry</button>
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Amount received <span class="text-danger">*</span></label>
                            <div class="tm-field-icon">
                                <span style="position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:#9aa1b0;">&#8377;</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="paymentAmountInput" value="{{ old('amount') }}" class="form-control tm-field @error('amount') is-invalid @enderror" required>
                            </div>
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Payment date <span class="text-danger">*</span></label>
                            <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-control tm-field @error('paid_at') is-invalid @enderror" required>
                            @error('paid_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3" style="font-size: .78rem; color: #1f6b30; font-weight: 600;" id="paymentFullBalanceNote"></div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Payment mode <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap" id="paymentModeButtons">
                            @foreach (['UPI', 'Bank transfer', 'Cash', 'Cheque'] as $mode)
                                <button type="button" class="btn btn-outline-secondary btn-sm js-mode-button flex-grow-1" data-mode="{{ $mode }}" data-label="{{ $mode }}" style="font-size: .8rem;">{{ $mode }}</button>
                            @endforeach
                        </div>
                        @error('mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block" id="paymentReferenceLabel">Reference</label>
                        <input type="text" name="reference" value="{{ old('reference') }}" maxlength="100" class="form-control tm-field @error('reference') is-invalid @enderror" id="paymentReferenceInput">
                        <div class="form-text tm-muted" id="paymentReferenceHint"></div>
                        @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Note <span class="tm-muted fw-normal">(optional)</span></label>
                        <textarea name="note" rows="2" maxlength="500" class="form-control tm-field @error('note') is-invalid @enderror" placeholder="For example, paid by the client's accountant">{{ old('note') }}</textarea>
                        @error('note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="rounded-3 p-3 mb-2" id="paymentConsequenceBox" style="background: #eef9ef; border: 1px solid #cdeccb; font-size: .82rem;">
                        <span id="paymentConsequenceText"></span>
                    </div>
                    <div class="d-flex gap-2" style="font-size: .76rem; color: #6b7280;">
                        <span>&#9432;</span>
                        <span>If you enter less than the balance, it's saved as a partial payment and the enquiry stays open.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-tm-primary" id="paymentSubmitButton">Record payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <form method="POST" id="editPaymentForm">
                @csrf
                @method('PUT')

                <div class="d-flex align-items-start gap-3 p-3" style="background: #101b3d;">
                    <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,.12); color: #fff;">&#8377;</span>
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-0 text-white">Edit payment</h2>
                        <div class="small" id="editPaymentTicketLabel" style="color: rgba(255,255,255,.7);"></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="rounded-3 p-3 mb-3" style="background: #eef9ef; border: 1px solid #cdeccb;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fw-bold" id="editPaymentCardClient"></div>
                        </div>
                        <div class="mb-2" style="font-size: .82rem; color: #2f5fbe; font-weight: 600;" id="editPaymentCardTicket"></div>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fff;">
                                    <div class="tm-muted" style="font-size: .68rem;">Total fee</div>
                                    <div class="fw-bold" style="font-size: .85rem;" id="editPaymentCardTotal"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fff;">
                                    <div class="tm-muted" style="font-size: .68rem;">Other payments</div>
                                    <div class="fw-bold" style="font-size: .85rem;" id="editPaymentCardPaid"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 p-2" style="background: #fceaea;">
                                    <div style="font-size: .68rem; color: #7f1616;">Balance due</div>
                                    <div class="fw-bold" style="font-size: .85rem; color: #7f1616;" id="editPaymentCardBalance"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Amount received <span class="text-danger">*</span></label>
                            <div class="tm-field-icon">
                                <span style="position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:#9aa1b0;">&#8377;</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="editPaymentAmount" class="form-control tm-field" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Payment date <span class="text-danger">*</span></label>
                            <input type="date" name="paid_at" id="editPaymentDate" max="{{ now()->format('Y-m-d') }}" class="form-control tm-field" required>
                        </div>
                    </div>
                    <div class="mb-3" style="font-size: .78rem; color: #1f6b30; font-weight: 600;" id="editPaymentFullBalanceNote"></div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Payment mode <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap" id="editPaymentModeButtons">
                            @foreach (['UPI', 'Bank transfer', 'Cash', 'Cheque'] as $mode)
                                <button type="button" class="btn btn-outline-secondary btn-sm js-edit-mode-button flex-grow-1" data-mode="{{ $mode }}" data-label="{{ $mode }}" style="font-size: .8rem;">{{ $mode }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="mode" id="editPaymentModeInput" value="UPI">
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block" id="editPaymentReferenceLabel">Reference</label>
                        <input type="text" name="reference" id="editPaymentReference" maxlength="100" class="form-control tm-field">
                        <div class="form-text tm-muted" id="editPaymentReferenceHint"></div>
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Note <span class="tm-muted fw-normal">(optional)</span></label>
                        <textarea name="note" id="editPaymentNote" rows="2" maxlength="500" class="form-control tm-field"></textarea>
                    </div>

                    <div class="rounded-3 p-3" id="editPaymentConsequenceBox" style="background: #eef9ef; border: 1px solid #cdeccb; font-size: .82rem;">
                        <span id="editPaymentConsequenceText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-tm-primary" id="editPaymentSubmitButton">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content position-relative" style="border: 0; border-radius: .75rem;">
            <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 1rem; right: 1rem; background-color: #f3f4f7; border-radius: 50%; width: 30px; height: 30px; padding: 0; opacity: 1;"></button>

            <form method="POST" id="deletePaymentForm">
                @csrf
                @method('DELETE')

                <div class="modal-body p-4 pt-5 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 64px; height: 64px; background: #fce9e9;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </div>
                    <h2 class="h5 fw-bold mb-2">Delete payment?</h2>
                    <p class="mb-0">
                        The payment for <strong id="deletePaymentLabel"></strong> will be removed and the enquiry's balance recalculated. This can't be undone.
                    </p>
                </div>

                <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    (function () {
        var tabs = document.querySelectorAll('.tm-ticket-tab');
        var receivedTable = document.getElementById('receivedTable');
        var pendingTable = document.getElementById('pendingTable');
        var receivedFilters = document.getElementById('receivedFilters');
        var activeTab = 'pending';
        var dateFromInput = document.getElementById('paymentDateFrom');
        var dateToInput = document.getElementById('paymentDateTo');

        function activeRows() {
            var table = activeTab === 'received' ? receivedTable : pendingTable;
            return Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        }

        function refresh() {
            var search = document.getElementById('paymentsSearch').value.trim().toLowerCase();
            var searchAmount = search.replace(/,/g, '');
            var mode = document.getElementById('modeFilter').value;
            var receivedBy = document.getElementById('receivedByFilter').value;

            activeRows().forEach(function (row) {
                var matchesSearch = !search
                    || (row.getAttribute('data-client') || '').includes(search)
                    || (row.getAttribute('data-ticket') || '').includes(search)
                    || (row.getAttribute('data-service') || '').includes(search)
                    || (row.getAttribute('data-reference') || '').includes(search)
                    || (row.getAttribute('data-mode') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-amount') || '').includes(searchAmount)
                    || (row.getAttribute('data-phone') || '').includes(search)
                    || (row.getAttribute('data-date') || '').includes(search)
                    || (row.getAttribute('data-received-by') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-payment-type') || '').includes(search);
                var matchesMode = !mode || row.getAttribute('data-mode') === mode;
                var matchesReceivedBy = !receivedBy || row.getAttribute('data-received-by') === receivedBy;

                var matchesDate = true;
                var dateFrom = dateFromInput.value;
                var dateTo = dateToInput.value;
                if (dateFrom || dateTo) {
                    var ts = parseInt(row.getAttribute('data-date-ts'), 10);
                    if (!ts) {
                        matchesDate = false;
                    } else {
                        if (dateFrom && ts < Math.floor(new Date(dateFrom + 'T00:00:00').getTime() / 1000)) {
                            matchesDate = false;
                        }
                        if (dateTo && ts > Math.floor(new Date(dateTo + 'T23:59:59').getTime() / 1000)) {
                            matchesDate = false;
                        }
                    }
                }

                row.classList.toggle('d-none', !(matchesSearch && matchesMode && matchesReceivedBy && matchesDate));
            });
        }

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeTab = btn.getAttribute('data-tab');
                tabs.forEach(function (b) { b.classList.toggle('active', b === btn); });
                receivedTable.classList.toggle('d-none', activeTab !== 'received');
                pendingTable.classList.toggle('d-none', activeTab !== 'pending');
                receivedFilters.classList.toggle('d-none', activeTab !== 'received');
                refresh();
            });
        });

        document.getElementById('paymentsSearch').addEventListener('input', refresh);
        document.getElementById('modeFilter').addEventListener('change', refresh);
        document.getElementById('receivedByFilter').addEventListener('change', refresh);

        if (window.jQuery) {
            jQuery('#paymentDateRangeBtn').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: { format: 'DD MMM YYYY', cancelLabel: 'Cancel' },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 days': [moment().subtract(29, 'days'), moment()],
                    'This month': [moment().startOf('month'), moment().endOf('month')],
                    'Last month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                },
            }, function (start, end) {
                dateFromInput.value = start.format('YYYY-MM-DD');
                dateToInput.value = end.format('YYYY-MM-DD');
                document.getElementById('paymentDateRangeLabel').textContent = start.format('D MMM') + ' – ' + end.format('D MMM YYYY');
                refresh();
            });

            jQuery('#paymentDateRangeBtn').on('cancel.daterangepicker', function () {
                dateFromInput.value = '';
                dateToInput.value = '';
                document.getElementById('paymentDateRangeLabel').textContent = 'All time';
                refresh();
            });
        }

        var paymentEnquiries = @json($paymentEnquiriesData);

        var enquiryPicker = document.getElementById('paymentEnquiryPicker');
        var enquirySelect = document.getElementById('paymentEnquirySelect');
        var enquiryCard = document.getElementById('paymentEnquiryCard');
        var enquiryIdInput = document.getElementById('paymentEnquiryIdInput');
        var amountInput = document.getElementById('paymentAmountInput');
        var fullBalanceNote = document.getElementById('paymentFullBalanceNote');
        var consequenceText = document.getElementById('paymentConsequenceText');
        var submitButton = document.getElementById('paymentSubmitButton');
        var modeInput = document.getElementById('paymentModeInput');
        var referenceLabel = document.getElementById('paymentReferenceLabel');
        var referenceInput = document.getElementById('paymentReferenceInput');
        var referenceHint = document.getElementById('paymentReferenceHint');

        function rupees(amount) {
            return '₹' + Number(amount || 0).toLocaleString('en-IN');
        }

        function selectEnquiry(id) {
            var info = paymentEnquiries[id];
            enquiryIdInput.value = id || '';
            if (!info) {
                enquiryPicker.classList.remove('d-none');
                enquiryCard.classList.add('d-none');
                return;
            }

            enquiryPicker.classList.add('d-none');
            enquiryCard.classList.remove('d-none');
            document.getElementById('paymentCardClient').textContent = info.client;
            var statusBadge = document.getElementById('paymentCardStatus');
            statusBadge.textContent = info.ticketCount + (info.ticketCount === 1 ? ' ticket' : ' tickets');
            statusBadge.classList.toggle('text-bg-success', info.allClosed);
            statusBadge.classList.toggle('text-bg-secondary', !info.allClosed);
            document.getElementById('paymentCardTicket').textContent = info.enquiry;
            document.getElementById('paymentCardTotal').textContent = rupees(info.total);
            document.getElementById('paymentCardPaid').textContent = rupees(info.paid);
            document.getElementById('paymentCardBalance').textContent = rupees(info.balance);

            amountInput.max = info.balance;
            amountInput.value = info.balance;
            updatePreview();
        }

        function updatePreview() {
            var info = paymentEnquiries[enquiryIdInput.value];
            var amount = parseFloat(amountInput.value) || 0;

            if (!info) {
                fullBalanceNote.textContent = '';
                consequenceText.innerHTML = '';
                submitButton.textContent = 'Record payment';
                return;
            }

            var remaining = Math.max(0, info.balance - amount);
            var isFull = amount >= info.balance && amount > 0;

            fullBalanceNote.innerHTML = isFull ? '&check; Full balance' : '';

            consequenceText.innerHTML = isFull
                ? '<strong>&check; Balance after this payment: ' + rupees(remaining) + '.</strong> This fully settles the balance due.'
                : (amount > 0
                    ? '<strong>Balance after this payment: ' + rupees(remaining) + '.</strong> This will be saved as a partial payment and the enquiry stays open.'
                    : '');

            submitButton.textContent = amount > 0 ? 'Record ' + rupees(amount) : 'Record payment';
        }

        enquirySelect.addEventListener('change', function () {
            selectEnquiry(enquirySelect.value);
        });

        amountInput.addEventListener('input', updatePreview);

        document.querySelector('.js-change-ticket').addEventListener('click', function () {
            enquiryIdInput.value = '';
            enquirySelect.value = '';
            enquiryPicker.classList.remove('d-none');
            enquiryCard.classList.add('d-none');
            updatePreview();
        });

        var referenceLabels = {
            'UPI': 'UPI reference number',
            'Bank transfer': 'Bank transfer reference',
            'Cash': 'Reference',
            'Cheque': 'Cheque number',
        };

        function selectMode(mode) {
            modeInput.value = mode;
            document.querySelectorAll('.js-mode-button').forEach(function (btn) {
                var active = btn.getAttribute('data-mode') === mode;
                btn.classList.toggle('active', active);
                btn.classList.toggle('btn-outline-primary', active);
                btn.classList.toggle('btn-outline-secondary', !active);
                btn.textContent = active ? ('✓ ' + btn.getAttribute('data-label')) : btn.getAttribute('data-label');
            });
            referenceLabel.textContent = referenceLabels[mode] || 'Reference';
            referenceInput.required = mode !== 'Cash';
            referenceHint.textContent = mode === 'Cash' ? 'Not needed for cash payments' : '';
        }

        document.querySelectorAll('.js-mode-button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectMode(btn.getAttribute('data-mode'));
            });
        });

        document.querySelectorAll('.js-record-payment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectEnquiry(btn.getAttribute('data-enquiry-id'));
            });
        });

        var deepLinkEnquiryId = new URLSearchParams(window.location.search).get('enquiry');
        if (deepLinkEnquiryId && paymentEnquiries[deepLinkEnquiryId]) {
            selectEnquiry(deepLinkEnquiryId);
            new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
        }

        document.getElementById('recordPaymentModal').addEventListener('show.bs.modal', function (event) {
            if (deepLinkEnquiryId && paymentEnquiries[deepLinkEnquiryId]) {
                deepLinkEnquiryId = null;
                return;
            }
            if (!event.relatedTarget || !event.relatedTarget.classList.contains('js-record-payment')) {
                enquiryIdInput.value = '';
                enquirySelect.value = '';
                enquiryPicker.classList.remove('d-none');
                enquiryCard.classList.add('d-none');
                amountInput.value = '';
            }
            selectMode(modeInput.value || 'UPI');
            updatePreview();
        });

        var editForm = document.getElementById('editPaymentForm');
        var editAmountInput = document.getElementById('editPaymentAmount');
        var editModeInput = document.getElementById('editPaymentModeInput');
        var editReferenceLabel = document.getElementById('editPaymentReferenceLabel');
        var editReferenceInput = document.getElementById('editPaymentReference');
        var editReferenceHint = document.getElementById('editPaymentReferenceHint');
        var editFullBalanceNote = document.getElementById('editPaymentFullBalanceNote');
        var editConsequenceText = document.getElementById('editPaymentConsequenceText');
        var editSubmitButton = document.getElementById('editPaymentSubmitButton');

        function updateEditPreview() {
            var balance = parseFloat(editForm.dataset.balance || '0');
            var amount = parseFloat(editAmountInput.value) || 0;
            var remaining = Math.max(0, balance - amount);
            var isFull = amount >= balance && amount > 0;

            editFullBalanceNote.innerHTML = isFull ? '&check; Full balance' : '';

            if (amount > balance) {
                editConsequenceText.innerHTML = '<strong style="color:#dc3545;">Amount can\'t exceed the balance due (' + rupees(balance) + ').</strong>';
            } else if (isFull) {
                editConsequenceText.innerHTML = '<strong>&check; Balance after saving: ' + rupees(remaining) + '.</strong> This fully settles the balance due.';
            } else if (amount > 0) {
                editConsequenceText.innerHTML = '<strong>Balance after saving: ' + rupees(remaining) + '.</strong> The enquiry stays open.';
            } else {
                editConsequenceText.innerHTML = '';
            }

            editSubmitButton.textContent = amount > 0 ? 'Save ₹' + Number(amount).toLocaleString('en-IN') : 'Save changes';
        }

        function selectEditMode(mode) {
            editModeInput.value = mode;
            document.querySelectorAll('.js-edit-mode-button').forEach(function (btn) {
                var active = btn.getAttribute('data-mode') === mode;
                btn.classList.toggle('active', active);
                btn.classList.toggle('btn-outline-primary', active);
                btn.classList.toggle('btn-outline-secondary', !active);
                btn.textContent = active ? ('✓ ' + btn.getAttribute('data-label')) : btn.getAttribute('data-label');
            });
            editReferenceLabel.textContent = referenceLabels[mode] || 'Reference';
            editReferenceInput.required = mode !== 'Cash';
            editReferenceHint.textContent = mode === 'Cash' ? 'Not needed for cash payments' : '';
        }

        document.querySelectorAll('.js-edit-mode-button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectEditMode(btn.getAttribute('data-mode'));
            });
        });

        document.querySelectorAll('.js-edit-payment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editForm.action = btn.getAttribute('data-action');
                editForm.dataset.balance = btn.getAttribute('data-balance');
                document.getElementById('editPaymentTicketLabel').textContent = btn.getAttribute('data-ticket');
                document.getElementById('editPaymentCardClient').textContent = btn.getAttribute('data-client');
                document.getElementById('editPaymentCardTicket').textContent = btn.getAttribute('data-ticket');
                document.getElementById('editPaymentCardTotal').textContent = rupees(btn.getAttribute('data-total'));
                document.getElementById('editPaymentCardPaid').textContent = rupees(btn.getAttribute('data-paid-excluding'));
                document.getElementById('editPaymentCardBalance').textContent = rupees(parseFloat(btn.getAttribute('data-balance')) + parseFloat(btn.getAttribute('data-amount')));
                editAmountInput.value = btn.getAttribute('data-amount');
                editReferenceInput.value = btn.getAttribute('data-reference') || '';
                document.getElementById('editPaymentNote').value = btn.getAttribute('data-note') || '';
                document.getElementById('editPaymentDate').value = btn.getAttribute('data-paid-at');
                selectEditMode(btn.getAttribute('data-mode'));
                updateEditPreview();
            });
        });

        editAmountInput.addEventListener('input', updateEditPreview);

        var deleteForm = document.getElementById('deletePaymentForm');
        document.querySelectorAll('.js-delete-payment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteForm.action = btn.getAttribute('data-action');
                document.getElementById('deletePaymentLabel').textContent = btn.getAttribute('data-ticket-label');
            });
        });
    })();
</script>
@endpush
