@extends('layouts.app')

@section('title', 'Tickets — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
<style>
    #ticketDateRangeBtn {
        font-size: .78rem;
        font-weight: 600;
        color: var(--tm-text);
    }
    #ticketDateRangeBtn:hover,
    #ticketDateRangeBtn:focus,
    #ticketDateRangeBtn:active {
        background-color: transparent !important;
        color: var(--tm-text) !important;
        border-color: var(--tm-surface-border) !important;
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
    $isAdmin = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();

    $pendingCards = [
        ['label' => 'Pending tickets', 'count' => $stats['pending_total'], 'caption' => $isAdmin ? 'across the firm' : 'assigned to you', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Waiting for client', 'count' => $stats['waiting_for_client'], 'caption' => 'documents not received', 'gradient' => 'linear-gradient(135deg, #3a2205, #8a5a10)'],
        ['label' => 'In progress', 'count' => $stats['in_progress'], 'caption' => 'being worked on', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Awaiting payment', 'count' => $stats['awaiting_payment'], 'caption' => 'task completed', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
    ];

    $historyCards = [
        ['label' => 'Closed tickets', 'count' => $stats['closed_total'], 'caption' => 'all time', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Closed this month', 'count' => $stats['closed_this_month'], 'caption' => now()->format('F'), 'gradient' => 'linear-gradient(135deg, #062a28, #0f766e)'],
        ['label' => 'Fees collected', 'count' => $stats['fees_collected'], 'prefix' => '₹', 'caption' => $isAdmin ? 'across the firm' : 'on your tickets', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Average time', 'count' => $stats['avg_days'], 'suffix' => ' days', 'caption' => 'created to completed', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
    ];
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $isAdmin ? 'Tickets' : 'My tickets']]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="tm-serif fw-bold mb-1" style="font-size: 1.15rem;">{{ $isAdmin ? 'Tickets' : 'My tickets' }}</h1>
        <p class="tm-muted mb-0" id="ticketsSubtitle" style="font-size: .8rem;">{{ $isAdmin ? 'All tickets in progress, from new to awaiting final payment' : 'Tickets assigned to you, from new to awaiting final payment' }}</p>
    </div>
    <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
</div>

<div id="pendingCards" class="row g-3 mb-4">
    @foreach ($pendingCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['count'] }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div id="historyCards" class="row g-3 mb-4 d-none">
    @foreach ($historyCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['prefix'] ?? '' }}{{ number_format($card['count']) }}{{ $card['suffix'] ?? '' }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0">
    <div class="d-flex align-items-center gap-4 px-3 pt-3">
        <button type="button" class="tm-ticket-tab active" data-tab="pending">
            Open Tickets <span class="badge rounded-pill text-bg-light border ms-1">{{ $pending->count() }}</span>
        </button>
        <button type="button" class="tm-ticket-tab" data-tab="history">
            Completed <span class="badge rounded-pill text-bg-light border ms-1">{{ $history->count() }}</span>
        </button>
    </div>
    <hr class="mt-2 mb-0">

    <div class="p-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="flex-grow-1" style="max-width: 320px;">
            <input type="text" id="ticketSearch" class="form-control tm-field" placeholder="Search ticket no., client, phone or status">
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div id="ticketDateRangeWrap" class="d-none" style="min-width: 220px;">
                <button type="button" id="ticketDateRangeBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span id="ticketDateRangeLabel" class="flex-grow-1 text-start" style="font-weight: 400;">All time</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <input type="hidden" id="ticketDateFrom">
                <input type="hidden" id="ticketDateTo">
            </div>
            <select id="sortOrder" class="form-select tm-field">
                <option value="desc" selected>Newest first</option>
                <option value="asc">Oldest first</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0" id="pendingTable">
            <thead>
                <tr>
                    <th class="tm-th-sortable" data-sort-key="number" data-sort-type="text">Ticket<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="client" data-sort-type="text">Client<x-tm-sort-icon /></th>
                    @if ($isAdmin)
                        <th class="tm-th-sortable" data-sort-key="employee" data-sort-type="text">Assigned to<x-tm-sort-icon /></th>
                    @endif
                    <th class="tm-th-sortable" data-sort-key="status" data-sort-type="text">Status<x-tm-sort-icon /></th>
                    <th>Documents</th>
                    <th class="tm-th-sortable" data-sort-key="open-days" data-sort-type="number">Open for<x-tm-sort-icon /></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending as $i => $ticket)
                    @php
                        $openDays = $ticket->created_at->diffInDays(now());
                        $ticketDocs = $ticket->service->documents;
                        $verifiedDocs = $ticketDocs->filter(function ($serviceDocument) use ($ticket) {
                            $current = $ticket->documents->where('service_document_id', $serviceDocument->id)->sortByDesc('version')->first();

                            return $current?->status === 'Verified';
                        })->count();
                    @endphp
                    <tr
                        data-number="{{ $ticket->number }}"
                        data-client="{{ $ticket->customer->name }}"
                        data-service="{{ $ticket->service->name }}"
                        data-employee="{{ $ticket->assignedTo->name ?? 'Unassigned' }}"
                        data-phone="{{ $ticket->customer->phone }}"
                        data-status="{{ $ticket->status->value }}"
                        data-open-days="{{ $openDays }}"
                        data-sort="{{ $i }}"
                    >
                        <td>
                            <div class="fw-semibold">{{ $ticket->number }}</div>
                            <div class="tm-muted" style="font-size: .78rem;">{{ $ticket->service->name }}</div>
                        </td>
                        <td>
                            <div>{{ $ticket->customer->name }}</div>
                            <div class="tm-muted" style="font-size: .78rem;">{{ $ticket->customer->phone }}</div>
                        </td>
                        @if ($isAdmin)
                            <td>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                        @endif
                        <td>
                            <x-status-badge :status="$ticket->status->value" />
                        </td>
                        <td class="small">
                            @if ($ticketDocs->isEmpty())
                                <span class="tm-muted">No documents required</span>
                            @else
                                <span style="color: {{ $verifiedDocs === $ticketDocs->count() ? '#1f6b30' : '#dc3545' }};">{{ $verifiedDocs }} of {{ $ticketDocs->count() }} verified</span>
                            @endif
                        </td>
                        <td>{{ $openDays }} days</td>
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="tm-icon-btn-sm" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state title="No open tickets" description="Tickets created from enquiries will show up here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-responsive d-none">
        <table class="table tm-table align-middle mb-0" id="historyTable">
            <thead>
                <tr>
                    <th class="tm-th-sortable" data-sort-key="number" data-sort-type="text">Ticket<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="client" data-sort-type="text">Client<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="service" data-sort-type="text">Service<x-tm-sort-icon /></th>
                    @if ($isAdmin)
                        <th class="tm-th-sortable" data-sort-key="employee" data-sort-type="text">Assigned to<x-tm-sort-icon /></th>
                    @endif
                    <th class="tm-th-sortable" data-sort-key="completed-ts" data-sort-type="number">Completed<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="fee" data-sort-type="number">Fee<x-tm-sort-icon /></th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $i => $ticket)
                    <tr
                        data-number="{{ $ticket->number }}"
                        data-client="{{ $ticket->customer->name }}"
                        data-service="{{ $ticket->service->name }}"
                        data-employee="{{ $ticket->assignedTo->name ?? 'Unassigned' }}"
                        data-completed-ts="{{ $ticket->completed_at?->timestamp }}"
                        data-fee="{{ $ticket->total }}"
                        data-sort="{{ $i }}"
                    >
                        <td class="fw-semibold">{{ $ticket->number }}</td>
                        <td>{{ $ticket->customer->name }}</td>
                        <td>{{ $ticket->service->name }}</td>
                        @if ($isAdmin)
                            <td>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                        @endif
                        <td>{{ $ticket->completed_at?->format('j M Y') }}</td>
                        <td>₹{{ number_format((float) $ticket->total) }}</td>
                        <td><x-status-badge :status="$ticket->status->value" /></td>
                        <td><x-payment-status-badge :status="$ticket->paymentStatus()" /></td>
                        <td><a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-empty-state title="No completed tickets yet" description="Tickets marked Task Completed will show up here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between p-3 small tm-muted" id="paginationBar">
        <span id="paginationSummary"></span>
        <span>
            <a href="#" id="paginationPrev" class="text-decoration-none">‹</a>
            Page <span id="paginationCurrent">1</span> of <span id="paginationTotal">1</span>
            <a href="#" id="paginationNext" class="text-decoration-none">›</a>
        </span>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    (function () {
        var PAGE_SIZE = 6;
        var subtitles = {
            pending: {{ Illuminate\Support\Js::from($isAdmin ? 'All tickets in progress, from new to awaiting final payment' : 'Tickets assigned to you, from new to awaiting final payment') }},
            history: {{ Illuminate\Support\Js::from('Closed tickets') }},
        };

        var activeTab = 'pending';
        var currentPage = 1;
        var sortState = {
            pending: { key: 'sort', dir: 'asc', type: 'number' },
            history: { key: 'sort', dir: 'desc', type: 'number' },
        };

        var tabs = document.querySelectorAll('.tm-ticket-tab');
        var pendingCards = document.getElementById('pendingCards');
        var historyCards = document.getElementById('historyCards');
        var pendingTable = document.getElementById('pendingTable').closest('.table-responsive');
        var historyTable = document.getElementById('historyTable').closest('.table-responsive');
        var subtitleEl = document.getElementById('ticketsSubtitle');
        var searchInput = document.getElementById('ticketSearch');
        var sortOrder = document.getElementById('sortOrder');
        var paginationSummary = document.getElementById('paginationSummary');
        var paginationCurrent = document.getElementById('paginationCurrent');
        var paginationTotal = document.getElementById('paginationTotal');
        var paginationPrev = document.getElementById('paginationPrev');
        var paginationNext = document.getElementById('paginationNext');
        var dateRangeWrap = document.getElementById('ticketDateRangeWrap');
        var dateFromInput = document.getElementById('ticketDateFrom');
        var dateToInput = document.getElementById('ticketDateTo');

        function activeTableEl() {
            return activeTab === 'pending' ? document.getElementById('pendingTable') : document.getElementById('historyTable');
        }

        function activeTableRows() {
            return Array.prototype.slice.call(activeTableEl().querySelectorAll('tbody tr'));
        }

        function updateSortIcons() {
            var sort = sortState[activeTab];
            var headers = activeTableEl().querySelectorAll('thead th.tm-th-sortable');

            headers.forEach(function (th) {
                th.classList.remove('tm-sort-active-asc', 'tm-sort-active-desc');
                if (th.getAttribute('data-sort-key') === sort.key) {
                    th.classList.add(sort.dir === 'asc' ? 'tm-sort-active-asc' : 'tm-sort-active-desc');
                }
            });
        }

        function render() {
            var search = searchInput.value.trim().toLowerCase();
            var sort = sortState[activeTab];

            var rows = activeTableRows();

            var dateFrom = activeTab === 'history' ? dateFromInput.value : '';
            var dateTo = activeTab === 'history' ? dateToInput.value : '';

            rows.forEach(function (row) {
                var matchesSearch = !search
                    || (row.getAttribute('data-number') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-client') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-service') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-phone') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-status') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-open-days') || '') === search;

                var matchesDate = true;
                if (dateFrom || dateTo) {
                    var ts = parseInt(row.getAttribute('data-completed-ts'), 10);
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

                row.dataset.visible = (matchesSearch && matchesDate) ? '1' : '0';
            });

            var visibleRows = rows.filter(function (row) { return row.dataset.visible === '1'; });

            visibleRows.sort(function (a, b) {
                var av = a.getAttribute('data-' + sort.key) || '';
                var bv = b.getAttribute('data-' + sort.key) || '';
                var diff = sort.type === 'number'
                    ? (parseFloat(av) - parseFloat(bv))
                    : av.localeCompare(bv);
                return sort.dir === 'desc' ? -diff : diff;
            });

            var totalPages = Math.max(1, Math.ceil(visibleRows.length / PAGE_SIZE));
            currentPage = Math.min(currentPage, totalPages);

            var start = (currentPage - 1) * PAGE_SIZE;
            var pageRows = visibleRows.slice(start, start + PAGE_SIZE);

            rows.forEach(function (row) { row.classList.add('d-none'); });
            pageRows.forEach(function (row) { row.classList.remove('d-none'); });
            visibleRows.forEach(function (row) { row.parentElement.appendChild(row); });

            paginationSummary.textContent = visibleRows.length
                ? 'Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, visibleRows.length) + ' of ' + visibleRows.length
                : 'No tickets found';
            paginationCurrent.textContent = currentPage;
            paginationTotal.textContent = totalPages;
        }

        function switchTab(tab) {
            activeTab = tab;
            currentPage = 1;

            tabs.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
            });

            pendingCards.classList.toggle('d-none', tab !== 'pending');
            historyCards.classList.toggle('d-none', tab !== 'history');
            pendingTable.classList.toggle('d-none', tab !== 'pending');
            historyTable.classList.toggle('d-none', tab !== 'history');
            dateRangeWrap.classList.toggle('d-none', tab !== 'history');
            sortOrder.classList.toggle('d-none', tab === 'history');
            subtitleEl.textContent = subtitles[tab];
            sortOrder.value = sortState[tab].dir;

            updateSortIcons();
            render();
        }

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () { switchTab(btn.getAttribute('data-tab')); });
        });

        searchInput.addEventListener('input', function () { currentPage = 1; render(); });

        sortOrder.addEventListener('input', function () {
            sortState[activeTab] = { key: 'sort', dir: sortOrder.value, type: 'number' };
            currentPage = 1;
            updateSortIcons();
            render();
        });

        document.querySelectorAll('#pendingTable thead th.tm-th-sortable, #historyTable thead th.tm-th-sortable').forEach(function (th) {
            th.addEventListener('click', function () {
                var table = th.closest('table');
                var tab = table.id === 'pendingTable' ? 'pending' : 'history';
                var key = th.getAttribute('data-sort-key');
                var type = th.getAttribute('data-sort-type');
                var current = sortState[tab];
                var dir = (current.key === key && current.dir === 'asc') ? 'desc' : 'asc';

                sortState[tab] = { key: key, dir: dir, type: type };

                if (tab === activeTab) {
                    currentPage = 1;
                    sortOrder.value = key === 'sort' ? dir : sortOrder.value;
                    updateSortIcons();
                    render();
                }
            });
        });

        paginationPrev.addEventListener('click', function (e) {
            e.preventDefault();
            if (currentPage > 1) { currentPage--; render(); }
        });

        paginationNext.addEventListener('click', function (e) {
            e.preventDefault();
            currentPage++;
            render();
        });

        if (window.jQuery) {
            jQuery('#ticketDateRangeBtn').daterangepicker({
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
                document.getElementById('ticketDateRangeLabel').textContent = start.format('D MMM') + ' – ' + end.format('D MMM YYYY');
                currentPage = 1;
                render();
            });

            jQuery('#ticketDateRangeBtn').on('cancel.daterangepicker', function () {
                dateFromInput.value = '';
                dateToInput.value = '';
                document.getElementById('ticketDateRangeLabel').textContent = 'All time';
                currentPage = 1;
                render();
            });
        }

        switchTab('pending');
    })();
</script>
@endpush
@endsection
