@extends('layouts.customer')

@section('title', 'My Tickets — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $tickets = [
        ['number' => 'TKT-2026-0001', 'service' => 'GST Return Filing', 'status' => 'Work In Progress', 'created' => '28 Sep 2026'],
        ['number' => 'TKT-2026-0002', 'service' => 'Income Tax Return Filing', 'status' => 'Task Completed', 'created' => '10 Sep 2026'],
        ['number' => 'TKT-2026-0003', 'service' => 'ROC Annual Filing', 'status' => 'Documents Pending', 'created' => '25 Sep 2026'],
    ];
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="h4 tm-serif fw-bold mb-1">My Tickets</h1>
        <p class="tm-muted mb-0" style="font-size: .89rem;">Track the status of every service you've requested</p>
    </div>
</div>

<div class="tm-card p-0">
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Created</th>
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
                        <td class="tm-muted">{{ $ticket['created'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
