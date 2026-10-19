@extends('layouts.customer')

@section('title', 'Payments — ' . config('app.firm_name'))

@section('content')
@php $money = fn ($amount) => '₹'.number_format((float) $amount); @endphp

<x-page-header title="Payments" subtitle="Fees billed, what you have paid and what is left" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'Payments']]">
    <x-slot:actions>
        @if ($fees['pending'] > 0)
            <button type="button" class="pbtn-navy" data-bs-toggle="modal" data-bs-target="#howToPayModal">₹ Make payment</button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4"><div class="p-stat blue"><div class="lb">Total fees</div><div class="vl">{{ $money($fees['billed']) }}</div><div class="sb">billed so far</div></div></div>
    <div class="col-6 col-md-4"><div class="p-stat green"><div class="lb">Paid</div><div class="vl">{{ $money($fees['paid']) }}</div><div class="sb">received</div></div></div>
    <div class="col-6 col-md-4"><div class="p-stat maroon"><div class="lb">Pending</div><div class="vl">{{ $money($fees['pending']) }}</div><div class="sb">still to pay</div></div></div>
</div>

@if ($fees['pending'] > 0)
    @php
        $upi = config('app.firm_upi_id');
        $bank = array_filter([
            'Account name' => config('app.firm_bank_account_name'),
            'Account number' => config('app.firm_bank_account_number'),
            'IFSC code' => config('app.firm_bank_ifsc'),
            'Bank and branch' => config('app.firm_bank_branch'),
        ]);
        $copyable = ['Account name', 'Account number', 'IFSC code'];
        $office = array_filter(['Address' => config('app.firm_address'), 'Hours' => config('app.firm_hours')]);
        $tabs = array_filter([
            'upi' => $upi ? 'UPI' : null,
            'bank' => $bank ? 'Bank transfer' : null,
            'office' => 'Pay at office',
        ]);
    @endphp
    <div class="modal fade p-pay" id="howToPayModal" tabindex="-1" aria-labelledby="howToPayTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="p-pay-head">
                    <div class="ic">₹</div>
                    <div>
                        <div class="tt" id="howToPayTitle">How to pay</div>
                        <div class="st">Choose any one way below</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-pay-due">
                        <div>
                            <div class="lb">Amount due</div>
                            <div class="sv">{{ $owing->map(fn ($row) => $row['name'].' '.$money($row['balance']))->implode(' · ') }}</div>
                        </div>
                        <div class="am">{{ $money($fees['pending']) }}</div>
                    </div>

                    <div class="p-pay-tabs" role="tablist">
                        @foreach ($tabs as $key => $label)
                            <button type="button" data-pay-tab="{{ $key }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    @if ($upi)
                        <div class="p-pay-box" data-pay-pane="upi">
                            <div class="p-pay-line">
                                <span class="lb">UPI ID</span><span class="vl">{{ $upi }}</span>
                                <button type="button" class="p-pay-copy" data-copy="{{ $upi }}">Copy</button>
                            </div>
                        </div>
                    @endif
                    @if ($bank)
                        <div class="p-pay-box" data-pay-pane="bank">
                            @foreach ($bank as $label => $value)
                                <div class="p-pay-line">
                                    <span class="lb">{{ $label }}</span><span class="vl">{{ $value }}</span>
                                    @if (in_array($label, $copyable, true))
                                        <button type="button" class="p-pay-copy" data-copy="{{ $value }}">Copy</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="p-pay-box" data-pay-pane="office">
                        <div class="p-pay-line"><span class="lb">Pay to</span><span class="vl">{{ config('app.firm_name') }}</span></div>
                        @foreach ($office as $label => $value)
                            <div class="p-pay-line"><span class="lb">{{ $label }}</span><span class="vl">{{ $value }}</span></div>
                        @endforeach
                    </div>

                    <div class="p-pay-note">
                        Please add <strong>{{ $reference }}</strong> in the payment note so we can match it to your account.
                        Once we record your payment, it shows as <strong>Paid</strong> here.
                    </div>
                </div>
                <div class="p-pay-foot">
                    <span>@if (config('app.firm_phone')) Questions? {{ config('app.firm_phone') }} @endif</span>
                    <button type="button" class="pbtn-outline" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('howToPayModal');
                var tabs = modal.querySelectorAll('[data-pay-tab]');
                var panes = modal.querySelectorAll('[data-pay-pane]');
                function show(key) {
                    tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.payTab === key); });
                    panes.forEach(function (p) { p.style.display = p.dataset.payPane === key ? '' : 'none'; });
                }
                tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.payTab); }); });
                show(tabs[0].dataset.payTab);

                modal.querySelectorAll('[data-copy]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.copy); }
                        var old = b.textContent; b.textContent = 'Copied';
                        setTimeout(function () { b.textContent = old; }, 1200);
                    });
                });

                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        </script>
    @endpush
@endif

<div class="p-card">
    <div class="p-card-head navy"><span>Payments received</span><span class="meta">{{ $payments->count() }}</span></div>

    @if ($payments->isEmpty())
        <div class="p-card-body text-center tm-muted u-8ae449">No payments recorded yet.</div>
    @else
        <div class="table-responsive">
            <table class="table tm-table align-middle mb-0">
                <thead>
                    <tr><th>Date</th><th>For</th><th>Mode</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('j M Y') }}</td>
                            <td>{{ $payment->enquiry?->tickets->pluck('service.name')->filter()->unique()->implode(', ') }}</td>
                            <td>{{ $payment->mode }}</td>
                            <td class="text-end fw-semibold">{{ $money($payment->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
