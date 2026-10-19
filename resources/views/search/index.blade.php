@extends('layouts.app')

@section('title', 'Search — ' . config('app.name', 'Task Management'))

@section('content')
@php $total = $tickets->count() + $enquiries->count() + $customers->count(); @endphp

<x-page-header
    title="Search"
    :subtitle="$term === '' ? 'Search by service number, client name, phone or email' : ($total.' '.\Illuminate\Support\Str::plural('result', $total).' for “'.$term.'”')"
    :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Search']]"
/>

@if (mb_strlen($term) < 2)
    <div class="tm-card p-4 text-center text-secondary">Type at least 2 characters in the search box at the top.</div>
@elseif ($total === 0)
    <div class="tm-card p-4 text-center text-secondary">Nothing matched “{{ $term }}”.</div>
@else
    @if ($tickets->isNotEmpty())
        <section class="tm-card p-0 mb-3 search-results-card">
            <div class="p-2 px-3 search-results-head search-results-head-navy">Services ({{ $tickets->count() }})</div>
            <div class="table-responsive">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Service</th><th>Client</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none">{{ $ticket->number }}</a>
                                    <div class="small text-secondary">{{ $ticket->service?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    @if ($ticket->customer)<a href="{{ route('customers.show', $ticket->customer) }}" class="fw-bold text-decoration-none">{{ $ticket->customer->name }}</a>@else—@endif
                                    <div class="small text-secondary">{{ $ticket->customer?->phone }}</div>
                                </td>
                                <td><x-status-badge :status="$ticket->status?->value ?? 'Unknown'" /></td>
                                <td class="text-end"><a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-primary">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($enquiries->isNotEmpty())
        <section class="tm-card p-0 mb-3 search-results-card">
            <div class="p-2 px-3 search-results-head search-results-head-green">Enquiries ({{ $enquiries->count() }})</div>
            <div class="table-responsive">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Enquiry</th><th>Client</th><th class="text-end">Total</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @foreach ($enquiries as $enquiry)
                            <tr>
                                <td><a href="{{ route('enquiries.show', $enquiry) }}" class="fw-semibold text-decoration-none">{{ $enquiry->number }}</a></td>
                                <td>
                                    @if ($enquiry->customer)<a href="{{ route('customers.show', $enquiry->customer) }}" class="fw-bold text-decoration-none">{{ $enquiry->customer->name }}</a>@else—@endif
                                    <div class="small text-secondary">{{ $enquiry->customer?->phone }}</div>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format((float) $enquiry->total) }}</td>
                                <td class="text-end"><a href="{{ route('enquiries.show', $enquiry) }}" class="btn btn-sm btn-primary">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($customers->isNotEmpty())
        <section class="tm-card p-0 mb-3 search-results-card">
            <div class="p-2 px-3 search-results-head search-results-head-navy">Clients ({{ $customers->count() }})</div>
            <div class="table-responsive">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Client</th><th>Phone</th><th>Email</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td><a href="{{ route('customers.show', $customer) }}" class="fw-semibold text-decoration-none">{{ $customer->name }}</a></td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->email }}</td>
                                <td class="text-end"><a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-primary">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endif
@endsection
