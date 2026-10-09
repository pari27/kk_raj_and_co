@extends('layouts.app')

@section('title', 'Clients — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    #clientsTable.tm-table thead th:first-child,
    #clientsTable.tm-table thead th:last-child {
        border-radius: 0;
    }
</style>
@endpush

@section('content')
@php
    $rows = $customers->map(function ($customer) {
        $ticketsOpen = $customer->tickets->filter(fn ($t) => ! $t->status->isClosed())->count();
        $feesPending = $customer->tickets->pluck('enquiry')->filter()->unique('id')->sum(fn ($enquiry) => $enquiry->balanceDue());

        return [
            'model' => $customer,
            'tickets_open' => $ticketsOpen,
            'tickets_total' => $customer->tickets->count(),
            'fees_pending' => $feesPending,
        ];
    });

    $totalCount = $rows->count();
    $withOpenTickets = $rows->filter(fn ($r) => $r['tickets_open'] > 0)->count();
    $feesPendingRows = $rows->filter(fn ($r) => $r['fees_pending'] > 0);
    $feesPendingTotal = $feesPendingRows->sum('fees_pending');
    $portalActive = $customers->where('portal_status', 'activated')->count();

    $avatarPalette = [
        ['bg' => '#e0edff', 'text' => '#2f5fbe'],
        ['bg' => '#e5f5e0', 'text' => '#2f8f3e'],
        ['bg' => '#ece5fb', 'text' => '#6d5bd0'],
        ['bg' => '#fbe5ea', 'text' => '#b91c4a'],
        ['bg' => '#fdecd2', 'text' => '#b9650a'],
    ];

    $portalLabels = [
        'activated' => ['text' => 'Portal: activated', 'color' => '#1f6b30'],
        'invite_sent' => ['text' => 'Portal: invite sent', 'color' => '#2f5fbe'],
        'not_logged_in' => ['text' => 'Portal: not logged in yet', 'color' => '#b9650a'],
    ];

    $statCards = [
        ['label' => 'Total clients', 'count' => $totalCount, 'caption' => 'this financial year', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'With open tickets', 'count' => $withOpenTickets, 'caption' => 'work in progress', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Fees pending', 'count' => $feesPendingTotal, 'prefix' => '₹', 'caption' => 'across ' . $feesPendingRows->count() . ' clients', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
        ['label' => 'Portal active', 'count' => $portalActive, 'caption' => 'clients logged in', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
    ];
@endphp

<x-page-header title="Clients" subtitle="Everyone the firm works for, with their tickets and fees" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Clients']]">
    <x-slot:actions>
        <a href="{{ route('customers.create') }}" class="btn btn-tm-primary">+ Add Client</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['prefix'] ?? '' }}{{ number_format($card['count']) }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
        <div class="tm-search d-flex align-items-center gap-2 px-3 py-2" style="max-width: 320px; background: #fff; border: 1px solid var(--tm-surface-border);">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="clientsSearch" placeholder="Search by name, phone or email" style="background: transparent; border: 0; outline: none; color: var(--tm-text); width: 100%; font-size: .85rem;">
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="sortLabel">Name A-Z</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item js-sort" href="#" data-col="0" data-dir="asc" data-label="Name A-Z">Name A-Z</a></li>
                    <li><a class="dropdown-item js-sort" href="#" data-col="0" data-dir="desc" data-label="Name Z-A">Name Z-A</a></li>
                    <li><a class="dropdown-item js-sort" href="#" data-col="2" data-dir="desc" data-label="Most tickets">Most tickets</a></li>
                    <li><a class="dropdown-item js-sort" href="#" data-col="2" data-dir="asc" data-label="Fewest tickets">Fewest tickets</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="clientsTable" class="table tm-table align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Tickets</th>
                    <th>Fees pending</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    @php
                        $customer = $row['model'];
                        $words = preg_split('/\s+/', trim($customer->name));
                        $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));
                        $palette = $customer->is_active ? $avatarPalette[$i % count($avatarPalette)] : ['bg' => '#eceef2', 'text' => '#6b7280'];
                        $portalInfo = $portalLabels[$customer->portal_status] ?? $portalLabels['not_logged_in'];
                    @endphp
                    <tr data-status="{{ $customer->is_active ? 'active' : 'inactive' }}">
                        <td data-order="{{ $customer->name }}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="tm-client-avatar" style="background: {{ $palette['bg'] }}; color: {{ $palette['text'] }};">{{ $initials }}</div>
                                <div>
                                    <a href="{{ route('customers.show', $customer) }}" class="fw-semibold text-decoration-none" style="color: {{ $customer->is_active ? 'inherit' : 'var(--tm-muted)' }};">{{ $customer->name }}</a>
                                    <div class="tm-muted" style="font-size: .78rem;">Client since {{ $customer->created_at->format('M Y') }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="tm-contact-line mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                {{ $customer->phone }}
                            </div>
                            <div class="tm-contact-line tm-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                {{ $customer->email ?? '—' }}
                            </div>
                        </td>
                        <td data-order="{{ $row['tickets_total'] }}">{{ $row['tickets_open'] }} open &middot; {{ $row['tickets_total'] }} total</td>
                        <td data-order="{{ $row['fees_pending'] }}">
                            @if ($row['fees_pending'] > 0)
                                <span class="fw-semibold" style="color: #c0392b;">₹{{ number_format($row['fees_pending']) }}</span>
                            @else
                                <span class="tm-muted">₹0</span>
                            @endif
                        </td>
                        <td>
                            <span class="d-flex align-items-center gap-2 small mb-1">
                                <span class="rounded-circle d-inline-block" style="width:7px;height:7px;background:{{ $customer->is_active ? '#4a9b3e' : '#9aa1b0' }};"></span>
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div style="font-size: .72rem; color: {{ $portalInfo['color'] }};">{{ $portalInfo['text'] }}</div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('customers.show', $customer) }}" class="tm-icon-btn-sm" title="View">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <a href="{{ route('customers.edit', $customer) }}" class="tm-icon-btn-sm" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    function stripHtml(html) {
        return $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
    }

    $(function () {
        var table = $('#clientsTable').DataTable({
            dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center px-3 py-3"i<"d-flex align-items-center gap-3"p>>',
            pageLength: 10,
            autoWidth: false,
            searching: true,
            order: [[0, 'asc']],
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ clients',
                infoEmpty: 'Showing 0 of 0 clients',
                paginate: { previous: '‹', next: '›' },
                emptyTable: '<div class="text-center py-5"><p class="fw-semibold mb-1">No clients yet</p><p class="tm-muted small mb-0">Add your first client to get started.</p></div>',
                zeroRecords: '<div class="text-center py-5"><p class="fw-semibold mb-1">No clients found</p><p class="tm-muted small mb-0">Try a different search.</p></div>',
            },
            columnDefs: [
                { targets: [5], orderable: false },
                {
                    targets: '_all',
                    render: {
                        _: function (data, type) {
                            if (type === 'filter' || type === 'sort') {
                                return stripHtml(data);
                            }
                            return data;
                        },
                    },
                },
                {
                    // Column 0 also renders a "Client since ..." caption, which
                    // would otherwise make every row match a search for "client".
                    targets: [0],
                    render: {
                        _: function (data, type, row, meta) {
                            if (type === 'filter' || type === 'sort') {
                                var cell = meta.settings.aoData[meta.row].anCells[meta.col];
                                var name = $(cell).find('a.fw-semibold').text();
                                return name || stripHtml(data);
                            }
                            return data;
                        },
                    },
                },
                {
                    // Fees pending is rendered as "₹19,000", whose comma breaks a
                    // search for "19000" and whose text sorts alphabetically, not
                    // numerically; use the cell's data-order attribute instead.
                    targets: [3],
                    render: {
                        _: function (data, type, row, meta) {
                            if (type === 'filter' || type === 'sort') {
                                var cell = meta.settings.aoData[meta.row].anCells[meta.col];
                                return $(cell).attr('data-order') || '0';
                            }
                            return data;
                        },
                    },
                },
            ],
        });

        $('#clientsSearch').on('keyup input', function () {
            table.search(this.value).draw();
        });

        $('.js-sort').on('click', function (e) {
            e.preventDefault();
            var col = $(this).data('col');
            var dir = $(this).data('dir');
            $('#sortLabel').text($(this).data('label'));
            table.order([col, dir]).draw();
        });
    });
</script>
@endpush
