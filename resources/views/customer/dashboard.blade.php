@extends('layouts.customer')

@section('title', 'My Dashboard — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $tickets = [
        ['number' => 'TKT-2026-0001', 'service' => 'GST Return Filing', 'status' => 'Work In Progress'],
        ['number' => 'TKT-2026-0002', 'service' => 'Income Tax Return Filing', 'status' => 'Task Completed'],
        ['number' => 'TKT-2026-0003', 'service' => 'ROC Annual Filing', 'status' => 'Documents Pending'],
    ];
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="h4 tm-serif fw-bold mb-1">Welcome, Meera Traders</h1>
        <p class="tm-muted mb-0" style="font-size: .89rem;">Here's the status of your work with us</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="tm-card p-3 h-100">
            <div class="tm-muted small mb-2">Total tickets</div>
            <div class="h3 tm-serif fw-bold mb-0">3</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="tm-card p-3 h-100">
            <div class="tm-muted small mb-2">Documents pending from you</div>
            <div class="h3 tm-serif fw-bold mb-0 text-warning">1</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="tm-card p-3 h-100">
            <div class="tm-muted small mb-2">Fees pending</div>
            <div class="h3 tm-serif fw-bold mb-0 text-warning">₹2,500</div>
        </div>
    </div>
</div>

<div class="tm-card p-0">
    <div class="d-flex align-items-center justify-content-between p-3 pb-0">
        <h2 class="h6 tm-serif fw-bold mb-0">My tickets</h2>
        <a href="{{ route('customer.tickets.index') }}" class="small text-decoration-underline">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tickets as $i => $ticket)
                    <tr>
                        <td class="fw-semibold">
                            <a href="{{ route('customer.tickets.show', $i + 1) }}" class="text-decoration-none" style="color: inherit;">{{ $ticket['number'] }}</a>
                        </td>
                        <td>{{ $ticket['service'] }}</td>
                        <td><x-status-badge :status="$ticket['status']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
