@extends('layouts.app')

@section('title', 'Employee Dashboard — ' . config('app.name', 'Task Management'))

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', auth()->user()->name)[0];
@endphp

@section('content')
<x-page-header
    :title="$greeting . ', ' . $firstName"
    :subtitle="now()->format('l, j F Y') . ' · Here’s your work for today'"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    <x-slot:actions>
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <{!! isset($card['url']) ? 'a href="'.e($card['url']).'"' : 'div' !!} class="tm-stat-card emp-dash-stat emp-dash-stat-{{ $card['gradient'] }}">
                <div class="lb">{{ $card['label'] }}</div>
                <div class="vl js-count-up" data-count="{{ $card['count'] }}">{{ $card['count'] }}</div>
                <div class="sb">{{ $card['sub'] }}</div>
            </{{ isset($card['url']) ? 'a' : 'div' }}>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3 align-items-start">
    <div class="col-12 col-xl-8">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-red"><span>Needs your attention</span></div>
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
                <div class="p-4 text-center text-secondary small">You are all caught up. Nothing needs your attention right now.</div>
            @endforelse
        </section>
    </div>

    <div class="col-12 col-xl-4">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-navy">
                <span>My services by status</span>
                <a href="{{ route('tickets.index') }}">My services →</a>
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

<div class="row g-3 align-items-start">
    <div class="col-12 col-xl-8">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-navy">
                <span>Services to work on</span>
                <a href="{{ route('tickets.index') }}">All my services →</a>
            </div>
            <div class="table-responsive">
                <table class="table emp-dash-table align-middle mb-0">
                    <thead>
                        <tr><th>Service</th><th>Client</th><th>Status</th><th class="text-end">Open for</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($workList as $row)
                            @php $ticket = $row['ticket']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="emp-dash-no">{{ $ticket->number }}</a>
                                    <div class="small text-secondary">{{ $ticket->service?->name ?? '—' }}</div>
                                </td>
                                <td class="fw-semibold">{{ $ticket->customer?->name ?? '—' }}</td>
                                <td><span class="emp-pill emp-pill-{{ \Illuminate\Support\Str::slug($ticket->status?->value ?? 'unknown') }}">{{ $ticket->status?->value ?? 'Unknown' }}</span></td>
                                <td class="text-end fw-semibold {{ $row['days'] >= 30 ? 'text-danger' : '' }}">{{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }}</td>
                                <td class="text-end"><a href="{{ route('tickets.show', $ticket) }}" class="tm-icon-btn-sm" title="View" aria-label="View service"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-5">No services are assigned to you yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-4">
        <section class="emp-dash-card tm-card-hover">
            <div class="emp-dash-head emp-dash-head-green"><span>Clients to follow up</span></div>
            @forelse ($followUps as $row)
                @php $ticket = $row['ticket']; $phone = preg_replace('/[^\d+]/', '', (string) $ticket->customer?->phone); @endphp
                <div class="emp-dash-follow">
                    <div class="flex-grow-1 min-w-0">
                        <div class="tt">{{ $ticket->customer?->name ?? '—' }}</div>
                        <div class="sb">{{ $ticket->service?->name ?? '—' }} · <span class="{{ $row['days'] >= 14 ? 'text-danger' : '' }} fw-bold">{{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }}</span></div>
                    </div>
                    @if ($phone !== '')
                        <a href="tel:{{ $phone }}" class="emp-dash-call" aria-label="Call {{ $ticket->customer?->name }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"></path></svg>
                        </a>
                    @endif
                </div>
            @empty
                <div class="p-4 text-center text-secondary small">No clients are holding up your services.</div>
            @endforelse
        </section>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.js-count-up').forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-count')) || 0;
            var duration = 1200;
            var start = null;

            function step(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);

                el.textContent = Math.round(eased * target).toLocaleString('en-IN');

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target.toLocaleString('en-IN');
                }
            }

            requestAnimationFrame(step);
        });
    })();
</script>
@endpush
@endsection
