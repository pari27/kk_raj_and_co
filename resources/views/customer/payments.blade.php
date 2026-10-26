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
    @include('customer._how-to-pay-modal', [
        'amount' => $fees['pending'],
        'lines' => $owing->map(fn ($row) => $row['name'].' '.$money($row['balance']))->implode(' · '),
        'reference' => $reference,
        'autoOpen' => true,
    ])
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
