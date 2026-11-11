@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name', 'Task Management'))

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $money = fn ($amount): string => '₹'.number_format((float) $amount);
@endphp

@section('content')
<x-page-header
    :title="$greeting . ', ' . auth()->user()->name"
    :subtitle="now()->format('l, j F Y') . ' · ' . $financialYear"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    <x-slot:actions>
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl admin-dash-col">
            <a href="{{ $card['url'] }}" class="tm-stat-card emp-dash-stat emp-dash-stat-{{ $card['gradient'] }}">
                <div class="lb">{{ $card['label'] }}</div>
                <div class="vl js-count-up" data-count="{{ $card['count'] }}" data-prefix="{{ $card['prefix'] }}">{{ $card['prefix'] }}{{ number_format($card['count']) }}</div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3 align-items-start">
    <div class="col-12 col-xl-7">
        <section class="emp-dash-card tm-card-hover admin-dash-attn">
            <div class="emp-dash-head emp-dash-head-green"><span>Needs attention</span></div>
            @forelse ($attention as $item)
                <div class="emp-dash-attn">
                    <span class="bar bar-{{ $item['tone'] }}"></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="tt">{{ $item['title'] }}</div>
                        <div class="sb">{{ $item['detail'] }}</div>
                    </div>
                    <a href="{{ $item['url'] }}" class="emp-dash-action emp-dash-action-{{ $item['tone'] }}">{{ $item['action'] }} →</a>
                </div>
            @empty
                <div class="p-4 text-center text-secondary small">You are all caught up. Nothing needs attention right now.</div>
            @endforelse
        </section>
    </div>

    <div class="col-12 col-xl-5">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-navy">
                <span>Open services by status</span>
                <a href="{{ route('tickets.index') }}">All services →</a>
            </div>
            @forelse ($statusBreakdown as $row)
                <div class="emp-dash-status-row">
                    <span class="emp-pill emp-pill-{{ $row['slug'] }}">{{ $row['label'] }}</span>
                    <strong>{{ $row['count'] }}</strong>
                </div>
            @empty
                <div class="p-4 text-center text-secondary small">No open services right now.</div>
            @endforelse
            @if ($statusBreakdown)
                <div class="emp-dash-status-row emp-dash-status-total"><strong>Total open</strong><strong>{{ $openTotal }}</strong></div>
            @endif
        </section>
    </div>
</div>

<div class="row g-3 mb-3 align-items-start">
    <div class="col-12 col-xl-8">
        <section class="emp-dash-card tm-card-hover admin-dash-enq">
            <div class="emp-dash-head emp-dash-head-navy">
                <span>Open services</span>
                <a href="{{ route('tickets.index') }}">All services →</a>
            </div>
            <div class="table-responsive">
                <table class="table emp-dash-table align-middle mb-0">
                    <thead>
                        <tr><th>Service no.</th><th>Client</th><th>Service</th><th>Assigned to</th><th>Status</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($openServices as $ticket)
                            <tr>
                                <td><a href="{{ route('tickets.show', $ticket) }}" class="emp-dash-no">{{ $ticket->number }}</a></td>
                                <td class="fw-semibold">{{ $ticket->customer?->name ?? '—' }}</td>
                                <td class="text-secondary">{{ $ticket->service?->name ?? '—' }}</td>
                                <td class="text-secondary">{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                                <td><span class="emp-pill emp-pill-{{ \Illuminate\Support\Str::slug($ticket->status?->value ?? '') }}">{{ $ticket->status ? str_replace('Documents', 'Docs', $ticket->status->value) : '—' }}</span></td>
                                <td class="text-end"><a href="{{ route('tickets.show', $ticket) }}" class="tm-icon-btn-sm" title="View" aria-label="View service"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary py-5">No open services right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-4">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-green">
                <span>Staff workload</span>
                <a href="{{ route('reports.index') }}">Staff work report →</a>
            </div>
            <div class="table-responsive">
                <table class="table emp-dash-table align-middle mb-0">
                    <thead>
                        <tr><th>Staff</th><th class="text-end">Open</th><th class="text-end">Completed (FY)</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($staffWorkload as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-end fw-bold {{ $row['open'] >= 12 ? 'text-danger' : '' }}">{{ $row['open'] }}</td>
                                <td class="text-end fw-bold text-success">{{ $row['completed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary py-4">No employees yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<section class="emp-dash-card tm-card-hover">
    <div class="emp-dash-head emp-dash-head-navy">
        <span>Recent payments</span>
        <a href="{{ route('payments.index') }}">All payments →</a>
    </div>
    <div class="table-responsive">
        <table class="table emp-dash-table align-middle mb-0">
            <thead>
                <tr><th>Date</th><th>Client</th><th>Mode</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @forelse ($recentPayments as $payment)
                    <tr>
                        <td class="text-secondary">{{ $payment->paid_at?->format('j M') }}</td>
                        <td class="fw-semibold">{{ $payment->enquiry?->customer?->name ?? '—' }}</td>
                        <td class="text-secondary">{{ $payment->mode }}</td>
                        <td class="text-end fw-bold text-success">{{ $money($payment->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-5">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.js-count-up').forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-count')) || 0;
            var prefix = el.getAttribute('data-prefix') || '';
            var duration = 1200;
            var start = null;

            function step(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);

                el.textContent = prefix + Math.round(eased * target).toLocaleString('en-IN');

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = prefix + target.toLocaleString('en-IN');
                }
            }

            requestAnimationFrame(step);
        });
    })();
</script>
@endpush
