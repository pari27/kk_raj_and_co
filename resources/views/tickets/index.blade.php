@extends('layouts.app')

@section('title', 'Services — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
@endpush

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();

    // Indian digit grouping, e.g. 1,19,330
    $indian = function (int $n): string {
        $digits = (string) $n;

        if (strlen($digits) <= 3) {
            return $digits;
        }

        return preg_replace('/\B(?=(\d{2})+$)/', ',', substr($digits, 0, -3)).','.substr($digits, -3);
    };

    $pendingCards = [
        ['label' => 'Pending services', 'count' => $stats['pending_total'], 'caption' => $isAdmin ? 'across the firm' : 'assigned to you', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Waiting for documents', 'count' => $stats['waiting_for_client'], 'caption' => 'documents not received', 'gradient' => 'linear-gradient(135deg, #3a2205, #8a5a10)'],
        ['label' => 'Completed (this month)', 'count' => $stats['closed_this_month'], 'caption' => now()->format('F'), 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Pending payment', 'count' => $stats['awaiting_payment'], 'prefix' => '₹', 'caption' => 'task completed', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
    ];

@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => $isAdmin ? 'Services' : 'My services']]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="tm-serif fw-bold mb-1 u-e83e11">{{ $isAdmin ? 'Services' : 'My services' }}</h1>
        <p class="tm-muted mb-0 u-5a305f" id="ticketsSubtitle">{{ $isAdmin ? 'All services in progress, from new to awaiting final payment' : 'Services assigned to you, from new to awaiting final payment' }}</p>
    </div>
    <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
</div>

<div id="pendingCards" class="row g-3 mb-4">
    @foreach ($pendingCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle u-943986"></span>
                <span class="position-absolute rounded-circle u-e46b84"></span>
                <div class="position-relative">
                    <div class="small mb-2 u-bbebd4">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['prefix'] ?? '' }}{{ $indian($card['count']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0">
    <div class="d-flex align-items-center gap-4 px-3 pt-3">
        <button type="button" class="tm-ticket-tab active" data-tab="pending">
            Open Services <span class="badge rounded-pill text-bg-light border ms-1">{{ $pending->count() }}</span>
        </button>
        <button type="button" class="tm-ticket-tab" data-tab="history">
            Completed <span class="badge rounded-pill text-bg-light border ms-1">{{ $history->count() }}</span>
        </button>
    </div>
    <hr class="mt-2 mb-0">

    <div class="p-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="u-ef4432">
            <x-search-box id="ticketSearch" />
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div id="ticketDateRangeWrap" class="d-none u-ad9e48">
                <button type="button" id="ticketDateRangeBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span id="ticketDateRangeLabel" class="flex-grow-1 text-start u-fe3f5c">All time</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <input type="hidden" id="ticketDateFrom">
                <input type="hidden" id="ticketDateTo">
            </div>
            <select id="sortOrder" class="form-select tm-field">
                {{-- The rows arrive newest first, so "asc" (the order they came in) is newest first. --}}
                <option value="asc" selected>Newest first</option>
                <option value="desc">Oldest first</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0" id="pendingTable">
            <thead>
                <tr>
                    <th class="tm-th-sortable" data-sort-key="number" data-sort-type="text">Service<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="client" data-sort-type="text">Client<x-tm-sort-icon /></th>
                    @if ($isAdmin)
                        <th class="tm-th-sortable" data-sort-key="employee" data-sort-type="text">Assigned to<x-tm-sort-icon /></th>
                    @endif
                    <th class="tm-th-sortable" data-sort-key="status" data-sort-type="text">Status<x-tm-sort-icon /></th>
                    <th>Comment</th>
                    <th>Documents</th>
                    <th class="tm-th-sortable" data-sort-key="open-days" data-sort-type="number">Open for<x-tm-sort-icon /></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending as $i => $ticket)
                    @php
                        $openDays = (int) abs($ticket->created_at->diffInDays(now()));
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
                        data-comment="{{ $ticket->currentStatusComment() }}"
                        data-open-days="{{ $openDays }}"
                        data-sort="{{ $i }}"
                    >
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="ticket-id-link fw-semibold">{{ $ticket->number }}</a>
                            <div class="tm-muted u-31578e">{{ $ticket->service->name }}</div>
                        </td>
                        <td>
                            <div class="fw-bold"><a href="{{ route('customers.show', $ticket->customer) }}" class="ticket-id-link">{{ $ticket->customer->name }}</a></div>
                            <div class="tm-muted u-31578e">{{ $ticket->customer->phone }}</div>
                        </td>
                        @if ($isAdmin)
                            <td>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                        @endif
                        <td>
                            <x-status-badge :status="$ticket->status->value" />
                        </td>
                        <td>
                            @if ($comment = $ticket->currentStatusComment())
                                <div class="ticket-status-comment" title="{{ $comment }}">{{ $comment }}</div>
                            @endif
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
                        <td colspan="8">
                            <x-empty-state title="No open services" description="Services created from enquiries will show up here." />
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
                    <th class="tm-th-sortable" data-sort-key="number" data-sort-type="text">Service<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="client" data-sort-type="text">Client<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="service" data-sort-type="text">Service<x-tm-sort-icon /></th>
                    @if ($isAdmin)
                        <th class="tm-th-sortable" data-sort-key="employee" data-sort-type="text">Assigned to<x-tm-sort-icon /></th>
                    @endif
                    <th class="tm-th-sortable" data-sort-key="completed-ts" data-sort-type="number">Completed<x-tm-sort-icon /></th>
                    <th class="tm-th-sortable" data-sort-key="fee" data-sort-type="number">Fee<x-tm-sort-icon /></th>
                    <th>Status</th>
                    <th>Comment</th>
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
                        data-comment="{{ $ticket->currentStatusComment() }}"
                        data-completed-ts="{{ $ticket->completed_at?->timestamp }}"
                        data-fee="{{ $ticket->total }}"
                        data-sort="{{ $i }}"
                    >
                        <td class="fw-semibold"><a href="{{ route('tickets.show', $ticket) }}" class="ticket-id-link">{{ $ticket->number }}</a></td>
                        <td><a href="{{ route('customers.show', $ticket->customer) }}" class="ticket-id-link">{{ $ticket->customer->name }}</a></td>
                        <td>{{ $ticket->service->name }}</td>
                        @if ($isAdmin)
                            <td>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                        @endif
                        <td>{{ $ticket->completed_at?->format('j M Y') }}</td>
                        <td>₹{{ number_format((float) $ticket->total) }}</td>
                        <td>
                            <x-status-badge :status="$ticket->status->value" />
                        </td>
                        <td>
                            @if ($comment = $ticket->currentStatusComment())
                                <div class="ticket-status-comment" title="{{ $comment }}">{{ $comment }}</div>
                            @endif
                        </td>
                        <td><x-payment-status-badge :status="$ticket->paymentStatus()" /></td>
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="tm-icon-btn-sm" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <x-empty-state title="No completed services yet" description="Services marked Task Completed will show up here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between p-3 small tm-muted" id="paginationBar">
        <span id="paginationSummary"></span>
        <ul class="pagination pagination-sm mb-0" id="paginationPages"></ul>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    (function () {
        var PAGE_SIZE = 10;
        var subtitles = {
            pending: {{ Illuminate\Support\Js::from($isAdmin ? 'All services in progress, from new to awaiting final payment' : 'Services assigned to you, from new to awaiting final payment') }},
            history: {{ Illuminate\Support\Js::from('Closed services') }},
        };

        var activeTab = 'pending';
        var currentPage = 1;
        var sortState = {
            pending: { key: 'sort', dir: 'asc', type: 'number' },
            history: { key: 'sort', dir: 'desc', type: 'number' },
        };

        var tabs = document.querySelectorAll('.tm-ticket-tab');
        var pendingTable = document.getElementById('pendingTable').closest('.table-responsive');
        var historyTable = document.getElementById('historyTable').closest('.table-responsive');
        var subtitleEl = document.getElementById('ticketsSubtitle');
        var searchInput = document.getElementById('ticketSearch');
        var sortOrder = document.getElementById('sortOrder');
        var assigneeFilter = document.getElementById('assigneeFilter');
        var paginationSummary = document.getElementById('paginationSummary');
        var paginationPages = document.getElementById('paginationPages');
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
            var assigneeValue = assigneeFilter ? assigneeFilter.value : '';

            var dateFrom = activeTab === 'history' ? dateFromInput.value : '';
            var dateTo = activeTab === 'history' ? dateToInput.value : '';

            rows.forEach(function (row) {
                var matchesSearch = !search
                    || (row.getAttribute('data-number') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-client') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-service') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-phone') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-status') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-comment') || '').toLowerCase().includes(search)
                    || (row.getAttribute('data-employee') || '').toLowerCase().includes(search)
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

                var matchesAssignee = !assigneeValue || (row.getAttribute('data-employee') || '') === assigneeValue;

                row.dataset.visible = (matchesSearch && matchesDate && matchesAssignee) ? '1' : '0';
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
                : 'No services found';
            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            paginationPages.innerHTML = '';
            var add = function (label, page, disabled, active) {
                var li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                var a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = label;
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!disabled && !active) { currentPage = page; render(); }
                });
                li.appendChild(a);
                paginationPages.appendChild(li);
            };
            add('‹', currentPage - 1, currentPage <= 1, false);
            for (var i = 1; i <= totalPages; i++) { add(String(i), i, false, i === currentPage); }
            add('›', currentPage + 1, currentPage >= totalPages, false);
        }

        function switchTab(tab) {
            activeTab = tab;
            currentPage = 1;

            tabs.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
            });

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

        if (assigneeFilter) {
            assigneeFilter.addEventListener('change', function () { currentPage = 1; render(); });
        }

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

        switchTab(new URLSearchParams(window.location.search).get('tab') === 'completed' ? 'history' : 'pending');
    })();
</script>
@endpush
@endsection
