@extends('layouts.app')

@section('title', 'Enquiries — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
<style>
    #enquiriesTable.tm-table thead th:first-child,
    #enquiriesTable.tm-table thead th:last-child {
        border-radius: 0;
    }
</style>
@endpush

@section('content')
@php
    $isAdmin = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();
    $totalCount = $enquiries->count();
    $openCount = $enquiries->where('status', 'Open')->count();
    $closedCount = $totalCount - $openCount;
    $creators = $enquiries->pluck('createdBy.name')->filter()->unique()->sort()->values();

    $statCards = [
        ['label' => 'Enquiries this month', 'count' => $stats['enquiries_this_month'], 'caption' => now()->format('F'), 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Tickets created', 'count' => $stats['tickets_created'], 'caption' => 'from these enquiries', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Enquiry value', 'count' => $stats['enquiry_value'], 'prefix' => '₹', 'caption' => 'this month, including GST', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Open enquiries', 'count' => $stats['open_enquiries'], 'caption' => 'tickets still in progress', 'gradient' => 'linear-gradient(135deg, #3a2205, #8a5a10)'],
    ];
@endphp

<x-page-header title="Enquiries" :subtitle="$isAdmin ? 'Every client request, with the tickets created from it' : 'Enquiries with at least one ticket assigned to you'" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Enquiries']]">
    <x-slot:actions>
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
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
                    <div style="color: rgba(255,255,255,.75); font-size: .72rem;">{{ $card['caption'] }}</div>
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
            <input type="text" id="enquiriesSearch" placeholder="Search enquiry, client, service, creator, status or amount" style="background: transparent; border: 0; outline: none; color: var(--tm-text); width: 100%; font-size: .85rem;">
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div style="min-width: 180px;">
                <button type="button" id="enquiryDateRangeBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span id="enquiryDateRangeLabel" class="flex-grow-1 text-start" style="font-weight: 400;">All time</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <input type="hidden" id="enquiryDateFrom">
                <input type="hidden" id="enquiryDateTo">
            </div>
            
        </div>
    </div>

    <div class="table-responsive">
        <table id="enquiriesTable" class="table tm-table align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Enquiry</th>
                    <th>Client</th>
                    <th>Services</th>
                    <th>Tickets</th>
                    <th>Created</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($enquiries as $enquiry)
                    @php
                        $ticketsTotal = $enquiry->tickets->count();
                        $ticketsClosed = $enquiry->tickets->filter(fn ($t) => $t->status->isClosed())->count();
                        $ratio = $ticketsTotal > 0 ? $ticketsClosed / $ticketsTotal : 0;
                    @endphp
                    <tr data-status="{{ $enquiry->status }}" data-creator="{{ $enquiry->createdBy?->name }}" data-created-ts="{{ $enquiry->created_at->timestamp }}">
                        <td data-order="{{ $enquiry->created_at->timestamp }}">
                            <div class="fw-semibold" style="font-size: .8rem;">{{ $enquiry->number }}</div>
                            <div class="tm-muted" style="font-size: .72rem;">{{ $enquiry->created_at->format('j M Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size: .8rem;">{{ $enquiry->customer->name }}</div>
                            <div class="tm-muted" style="font-size: .72rem;">{{ $enquiry->customer->phone }}</div>
                        </td>
                        <td>
                            @foreach ($enquiry->tickets as $ticket)
                                <span class="badge rounded-pill text-bg-light border fw-normal mb-1" style="color: #2f5fbe; border-color: #cfe0ff !important; background: #eef4ff !important;">{{ $ticket->service->name }}</span>
                            @endforeach
                        </td>
                        <td data-order="{{ $ticketsClosed }}" style="min-width: 110px;">
                            <div class="small fw-semibold mb-1">{{ $ticketsClosed }} of {{ $ticketsTotal }} closed</div>
                            <div class="tm-progress-track">
                                <div class="tm-progress-fill" style="width: {{ $ratio * 100 }}%;"></div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: .8rem;">{{ $enquiry->createdBy?->name ?? '—' }}</div>
                            <div class="tm-muted" style="font-size: .72rem;">{{ $enquiry->created_at->format('j M Y') }}</div>
                        </td>
                        <td data-order="{{ $enquiry->total }}">
                            <div class="fw-semibold" style="font-size: .8rem;">₹{{ number_format((float) $enquiry->total) }}</div>
                            @if ($enquiry->discount > 0)
                                <div style="font-size: .72rem; color: #1f6b30;">₹{{ number_format((float) $enquiry->discount) }} off</div>
                            @endif
                        </td>
                        <td data-order="{{ $enquiry->status === 'Open' ? 0 : 1 }}">
                            <span class="badge rounded-pill fw-normal px-3 py-2" style="{{ $enquiry->status === 'Open' ? 'background:#eef4ff;color:#2f5fbe;' : 'background:#e5f5e0;color:#2f8f3e;' }}">
                                <span class="rounded-circle d-inline-block me-1" style="width:6px;height:6px;background:currentColor;"></span>{{ $enquiry->status }}
                            </span>
                        </td>
                        <td class="text-end" data-order="0">
                            <a href="{{ route('enquiries.show', $enquiry) }}" class="tm-icon-btn-sm" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    function stripHtml(html) {
        return $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
    }

    $(function () {
        var table = $('#enquiriesTable').DataTable({
            dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center px-3 py-3"i<"d-flex align-items-center gap-3"p>>',
            pageLength: 10,
            autoWidth: false,
            searching: true,
            order: [[0, 'desc']],
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ enquiries',
                infoEmpty: 'Showing 0 of 0 enquiries',
                paginate: { previous: '‹', next: '›' },
                emptyTable: '<div class="text-center py-5"><p class="fw-semibold mb-1">No enquiries yet</p><p class="tm-muted small mb-0">Create your first enquiry to get started.</p></div>',
                zeroRecords: '<div class="text-center py-5"><p class="fw-semibold mb-1">No enquiries found</p><p class="tm-muted small mb-0">Try a different search.</p></div>',
            },
            columnDefs: [
                { targets: [7], orderable: false },
                {
                    targets: '_all',
                    render: function (data, type) {
                        if (type !== 'filter' && type !== 'sort') {
                            return data;
                        }
                        return stripHtml(data);
                    },
                },
            ],
        });

        $('#enquiriesSearch').on('keyup input', function () {
            table.search(this.value).draw();
        });

        var statusFilter = 'all';
        var creatorFilter = '';
        var dateFromInput = document.getElementById('enquiryDateFrom');
        var dateToInput = document.getElementById('enquiryDateTo');

        $.fn.dataTable.ext.search.push(function (settings, data, index) {
            if (settings.nTable.id !== 'enquiriesTable') {
                return true;
            }

            var row = $(table.row(index).node());

            if (statusFilter !== 'all' && row.data('status') !== statusFilter) {
                return false;
            }

            if (creatorFilter && row.data('creator') !== creatorFilter) {
                return false;
            }

            var dateFrom = dateFromInput.value;
            var dateTo = dateToInput.value;

            if (dateFrom || dateTo) {
                var ts = parseInt(row.attr('data-created-ts'), 10);

                if (dateFrom && ts < Math.floor(new Date(dateFrom + 'T00:00:00').getTime() / 1000)) {
                    return false;
                }
                if (dateTo && ts > Math.floor(new Date(dateTo + 'T23:59:59').getTime() / 1000)) {
                    return false;
                }
            }

            return true;
        });

        $('.tm-filter-pill').on('click', function () {
            $('.tm-filter-pill').removeClass('active');
            $(this).addClass('active');
            statusFilter = $(this).data('filter');
            table.draw();
        });

        $('.js-creator-filter').on('click', function (e) {
            e.preventDefault();
            creatorFilter = $(this).data('creator');
            $('#creatorLabel').text(creatorFilter || 'All');
            table.draw();
        });

        jQuery('#enquiryDateRangeBtn').daterangepicker({
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
            document.getElementById('enquiryDateRangeLabel').textContent = start.format('D MMM') + ' – ' + end.format('D MMM YYYY');
            table.draw();
        });

        jQuery('#enquiryDateRangeBtn').on('cancel.daterangepicker', function () {
            dateFromInput.value = '';
            dateToInput.value = '';
            document.getElementById('enquiryDateRangeLabel').textContent = 'All time';
            table.draw();
        });
    });
</script>
@endpush
