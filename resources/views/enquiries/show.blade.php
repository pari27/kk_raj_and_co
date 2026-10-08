@extends('layouts.app')

@section('title', $enquiry->number . ' — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    #enquiryTicketsTable.tm-table thead th {
        background: #eceef2;
        color: #4b5563;
    }
    #enquiryTicketsTable.tm-table thead th:first-child,
    #enquiryTicketsTable.tm-table thead th:last-child {
        border-radius: 0;
    }
    .btn-outline-navy {
        color: #101b3d;
        border-color: #101b3d;
    }
    .btn-outline-navy:hover {
        color: #fff;
        background: #101b3d;
        border-color: #101b3d;
    }
</style>
@endpush

@section('content')
@php
    $statCards = [
        ['label' => 'Enquiry total', 'value' => '₹'.number_format((float) $enquiry->total), 'caption' => 'including GST', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Tickets', 'value' => (string) $enquiry->tickets->count(), 'caption' => $closedTicketsCount.' closed', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Fees received', 'value' => '₹'.number_format($feesReceived), 'caption' => $feesReceivedCount > 0 ? $feesReceivedCount.' payment(s)' : 'no payments yet', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Fees pending', 'value' => '₹'.number_format($feesPending), 'caption' => 'across '.$enquiry->tickets->count().' ticket(s)', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
    ];

    $portalLabels = [
        'activated' => ['text' => 'Portal: activated', 'color' => '#1f6b30'],
        'invite_sent' => ['text' => 'Portal: invite sent', 'color' => '#2f5fbe'],
        'not_logged_in' => ['text' => 'Portal: not logged in yet', 'color' => '#b9650a'],
    ];
    $portalInfo = $portalLabels[$enquiry->customer->portal_status] ?? $portalLabels['not_logged_in'];

    $words = preg_split('/\s+/', trim($enquiry->customer->name));
    $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));

    $dotColor = function (string $action): string {
        return match ($action) {
            'Created', 'Activated' => '#1f6b30',
            'Deactivated' => '#7f1616',
            default => '#0a4fc4',
        };
    };
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Enquiries', 'url' => route('enquiries.index')], ['label' => $enquiry->number]]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
            <h1 class="tm-serif fw-bold mb-0" style="font-size: 1.15rem;">{{ $enquiry->number }}</h1>
            <span class="badge rounded-pill text-bg-{{ $enquiry->status === 'Open' ? 'primary' : 'secondary' }} fw-normal">{{ $enquiry->status }}</span>
        </div>
        <p class="tm-muted mb-0" style="font-size: .8rem;">
            Created {{ $enquiry->created_at->format('j M Y, g:i A') }}{{ $enquiry->createdBy ? ' by '.$enquiry->createdBy->name : '' }}
            &middot; Last updated {{ $enquiry->updated_at->diffForHumans() }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ Add Enquiry</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h4 tm-serif fw-bold mb-1 text-white">{{ $card['value'] }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="tm-card p-0 mb-3" style="overflow: hidden;">
            <div class="p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Client</h2>
            </div>
            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background: #101b3d; color: #fff; font-weight: 700; font-size: .95rem;">
                        {{ $initials }}
                    </div>
                    <div>
                        <div class="fw-bold enquiry-client-name">{{ $enquiry->customer->name }}</div>
                        <div class="tm-muted" style="font-size: .8rem;">
                            &#128222; {{ $enquiry->customer->phone }} &nbsp; &#9993; {{ $enquiry->customer->email }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="mb-1" style="font-size: .72rem; color: {{ $portalInfo['color'] }};">
                        <span class="rounded-circle d-inline-block me-1" style="width: 6px; height: 6px; background: {{ $portalInfo['color'] }};"></span>{{ $portalInfo['text'] }}
                    </div>
                    <a href="{{ route('customers.show', $enquiry->customer) }}" class="small text-decoration-none fw-semibold enquiry-view-client-link">View client &rarr;</a>
                </div>
            </div>
        </div>

        <div class="tm-card p-0 mb-3" style="overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Tickets in this enquiry ({{ $enquiry->tickets->count() }})</h2>
                <span class="enquiry-ticket-heading-note" style="color: rgba(255,255,255,.7); font-size: .72rem;">One ticket per service</span>
            </div>
            @if ($enquiry->tickets->isEmpty())
                <div class="p-4">
                    <x-empty-state title="No tickets yet" description="Tickets created for this enquiry will show up here." />
                </div>
            @else
                <div class="table-responsive">
                    <table id="enquiryTicketsTable" class="table tm-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Assigned to</th>
                                <th>Status</th>
                                
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enquiry->tickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none enquiry-ticket-number">{{ $ticket->number }}</a>
                                        <div class="tm-muted enquiry-ticket-service">{{ $ticket->service?->name }}</div>
                                    </td>
                                    <td class="enquiry-ticket-assignee">{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                                    <td><x-status-badge :status="$ticket->status->value" /></td>
                                    <td class="text-end fw-semibold">₹{{ number_format((float) $ticket->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tm-card p-0" style="overflow: hidden;">
            <div class="p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Activity</h2>
            </div>
            <div class="p-4">
                @forelse ($activity as $log)
                    <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <span class="rounded-circle flex-shrink-0 mt-1" style="width: 8px; height: 8px; background: {{ $dotColor($log->action) }};"></span>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold activity-list-title">{{ $log->details ?? $log->action }}</div>
                            <div class="tm-muted" style="font-size: .75rem;">{{ $log->created_at->format('j M Y, g:i A') }}{{ $log->user ? ' · '.$log->user->name : '' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="tm-muted small mb-0">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card p-0 mb-3" style="overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between gap-2 p-3" style="background: #1f6b30;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Fee breakdown</h2>
                @if ($feesPending > 0)
                    <a href="#enquiryPaymentModal" role="button" data-bs-toggle="modal" class="enquiry-make-payment-button flex-shrink-0">Make payment</a>
                @endif
            </div>
            <ul class="list-unstyled p-3 mb-0" style="font-size: .85rem;">
                @foreach ($enquiry->tickets as $ticket)
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="tm-muted">{{ $ticket->service?->name }}</span>
                        <span class="fw-bold">₹{{ number_format((float) $ticket->price) }}</span>
                    </li>
                @endforeach
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">GST</span>
                    <span class="fw-bold">₹{{ number_format((float) $enquiry->gst_total) }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Discount</span>
                    <span class="fw-bold">₹{{ number_format((float) $enquiry->discount) }}</span>
                </li>
            </ul>
            <div class="mx-3 mb-2 rounded-3 p-3 d-flex justify-content-between align-items-center" style="background: #e5f5e0;">
                <div>
                    <div class="fw-bold">Total</div>
                    <div class="tm-muted" style="font-size: .72rem;">including GST</div>
                </div>
                <div class="h5 tm-serif fw-bold mb-0" style="color: #1f6b30;">₹{{ number_format((float) $enquiry->total) }}</div>
            </div>
            <div class="mx-3 mb-3 rounded-3 p-3 d-flex justify-content-between align-items-center" style="background: #fbe5e5;">
                <div class="fw-bold" style="color: #7f1616;">Pending</div>
                <div class="h6 fw-bold mb-0" style="color: #7f1616;">₹{{ number_format($feesPending) }}</div>
            </div>
        </div>

        <div class="tm-card p-0 mb-3" style="overflow: hidden;">
            <div class="p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Enquiry details</h2>
            </div>
            <ul class="list-unstyled p-3 mb-0 enquiry-details-list">
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Enquiry no.</span>
                    <span class="fw-bold">{{ $enquiry->number }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Created by</span>
                    <span class="fw-bold">{{ $enquiry->createdBy->name ?? '—' }}</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="tm-muted">Created on</span>
                    <span class="fw-bold">{{ $enquiry->created_at->format('j M Y') }}</span>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span class="tm-muted">Status</span>
                    <span class="fw-bold" style="color: {{ $enquiry->status === 'Open' ? '#2f5fbe' : 'var(--tm-muted)' }};">{{ $enquiry->status }}</span>
                </li>
            </ul>
        </div>

        @if ($enquiry->notes)
            <div class="tm-card p-0" style="overflow: hidden;">
                <div class="p-3" style="background: #1f6b30;">
                    <h2 class="h6 tm-serif fw-bold mb-0 text-white">Notes</h2>
                </div>
                <div class="p-3 small">{{ $enquiry->notes }}</div>
            </div>
        @endif
    </div>
</div>



@if ($feesPending > 0)
    <div class="modal fade" id="enquiryPaymentModal" tabindex="-1" aria-labelledby="enquiryPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered enquiry-payment-modal-dialog">
            <div class="modal-content enquiry-payment-modal-content">
                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
                    <input type="hidden" name="return_to_enquiry" value="1">
                    <input type="hidden" name="mode" id="enquiryPaymentMode" value="{{ old('mode', 'UPI') }}">

                    <div class="enquiry-payment-modal-header">
                        <span class="enquiry-payment-currency-icon">&#8377;</span>
                        <div class="flex-grow-1">
                            <h2 class="h6 fw-bold mb-0 text-white" id="enquiryPaymentModalLabel">Record payment</h2>
                            <div class="small enquiry-payment-modal-subtitle">Record an advance, partial or full payment for this enquiry</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        @error('enquiry_id')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror
                        <div class="enquiry-payment-summary">
                            <div class="fw-bold">{{ $enquiry->customer->name ?? 'Client' }}</div>
                            <div class="enquiry-payment-ticket">{{ $enquiry->number }}</div>
                            <div class="enquiry-payment-balance">Balance due: <strong>&#8377;{{ number_format($feesPending, 2) }}</strong></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="enquiryPaymentAmount" class="tm-field-label d-block">Amount received <span class="text-danger">*</span></label>
                                <div class="enquiry-payment-amount-field">
                                    <span>&#8377;</span>
                                    <input id="enquiryPaymentAmount" type="number" step="0.01" min="0.01" max="{{ number_format($feesPending, 2, '.', '') }}" name="amount" value="{{ old('amount') }}" class="form-control tm-field @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="enquiryPaymentDate" class="tm-field-label d-block">Payment date <span class="text-danger">*</span></label>
                                <input id="enquiryPaymentDate" type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-control tm-field @error('paid_at') is-invalid @enderror" required>
                                @error('paid_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="tm-field-label d-block">Payment mode <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 flex-wrap" id="enquiryPaymentModeButtons">
                                @foreach (['UPI', 'Bank transfer', 'Cash', 'Cheque'] as $mode)
                                    <button type="button" class="btn btn-outline-secondary btn-sm enquiry-payment-mode-button {{ old('mode', 'UPI') === $mode ? 'active' : '' }}" data-mode="{{ $mode }}">{{ $mode }}</button>
                                @endforeach
                            </div>
                            @error('mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="enquiryPaymentReference" class="tm-field-label d-block" id="enquiryPaymentReferenceLabel">Reference</label>
                            <input id="enquiryPaymentReference" type="text" name="reference" value="{{ old('reference') }}" maxlength="100" class="form-control tm-field @error('reference') is-invalid @enderror">
                            <div class="form-text tm-muted" id="enquiryPaymentReferenceHint"></div>
                            @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="enquiryPaymentNote" class="tm-field-label d-block">Note <span class="tm-muted fw-normal">(optional)</span></label>
                            <textarea id="enquiryPaymentNote" name="note" rows="2" maxlength="500" class="form-control tm-field @error('note') is-invalid @enderror" placeholder="For example, paid by the client's accountant">{{ old('note') }}</textarea>
                            @error('note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="enquiry-payment-partial-note">
                            If you enter less than the balance, it will be saved as a partial payment and the enquiry stays open.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-tm-primary" id="enquiryPaymentSubmitButton">Record payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($feesPending > 0)
    @push('scripts')
        <script>
            (function () {
                var modeInput = document.getElementById('enquiryPaymentMode');
                var modeButtons = document.querySelectorAll('#enquiryPaymentModeButtons .enquiry-payment-mode-button');
                var referenceLabel = document.getElementById('enquiryPaymentReferenceLabel');
                var referenceInput = document.getElementById('enquiryPaymentReference');
                var referenceHint = document.getElementById('enquiryPaymentReferenceHint');
                var referenceLabels = {
                    'UPI': 'UPI reference number',
                    'Bank transfer': 'Bank transfer reference',
                    'Cash': 'Reference',
                    'Cheque': 'Cheque number'
                };

                function selectMode(mode) {
                    modeInput.value = mode;
                    modeButtons.forEach(function (button) {
                        var isActive = button.getAttribute('data-mode') === mode;
                        button.classList.toggle('active', isActive);
                        button.classList.toggle('btn-outline-primary', isActive);
                        button.classList.toggle('btn-outline-secondary', !isActive);
                    });
                    referenceLabel.textContent = referenceLabels[mode] || 'Reference';
                    referenceInput.required = mode !== 'Cash';
                    referenceHint.textContent = mode === 'Cash' ? 'Not needed for cash payments' : '';
                }

                modeButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        selectMode(button.getAttribute('data-mode'));
                    });
                });
                selectMode(modeInput.value || 'UPI');
            })();
        </script>
    @endpush
@endif

@if ($errors->has('enquiry_id') || $errors->has('amount') || $errors->has('mode') || $errors->has('paid_at') || $errors->has('reference') || $errors->has('note'))
    @push('scripts')
        <script>
            var enquiryPaymentModal = document.getElementById('enquiryPaymentModal');
            if (enquiryPaymentModal) {
                bootstrap.Modal.getOrCreateInstance(enquiryPaymentModal).show();
            }
        </script>
    @endpush
@endif
@endsection
