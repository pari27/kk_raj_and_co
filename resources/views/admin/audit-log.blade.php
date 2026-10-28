@extends('layouts.app')

@section('title', 'Audit Log — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
@endpush

@php
    $statCards = [
        ['label' => 'Actions today', 'count' => $stats['actions_today'], 'caption' => 'all events recorded today', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Logins today', 'count' => $stats['logins_today'], 'caption' => 'successful sign-ins', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Records changed', 'count' => $stats['records_changed_today'], 'caption' => 'created, updated or toggled today', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
        ['label' => 'Failed logins', 'count' => $stats['failed_logins_today'], 'caption' => 'today', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
    ];

    $actionColors = [
        'Created' => 'success',
        'Activated' => 'success',
        'Logged in' => 'info',
        'Updated' => 'primary',
        'Deactivated' => 'secondary',
        'Logged out' => 'secondary',
        'Failed login' => 'danger',
    ];
@endphp

@section('content')
<x-page-header title="Audit Log" subtitle="{{ $ownOnly ? 'A record of your own actions in the app' : 'System-wide record of real actions taken across the app' }}" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Audit Log']]">
    <x-slot:actions>
        <a href="{{ route('admin.audit-log.export') }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Export
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle u-943986"></span>
                <span class="position-absolute rounded-circle u-e46b84"></span>
                <div class="position-relative">
                    <div class="small mb-2 u-bbebd4">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ number_format($card['count']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0">
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-9">
                <x-search-box id="auditLogSearch" />
            </div>
            <div class="col-12 col-md-3">
                <button type="button" id="auditLogDateRangeBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span class="text-uppercase tm-muted u-dcc109">Date range</span>
                    <span id="auditLogDateRangeLabel" class="flex-grow-1 text-start u-fe3f5c">All time</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <input type="hidden" id="auditLogDateFrom">
                <input type="hidden" id="auditLogDateTo">
            </div>
        </div>
    </div>

    @if ($logs->isEmpty())
        <x-empty-state title="No activity yet" description="Real actions taken in the app will show up here as they happen." />
    @else
    <div class="table-responsive">
        <table id="auditLogTable" class="table tm-table align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Details</th>
                    <th>Device</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    @php
                        $details = $log->details;
                        $arrowParts = $details && str_contains($details, ' → ') ? explode(' → ', $details, 2) : null;
                    @endphp
                    <tr data-user="{{ $log->user_id }}" data-module="{{ $log->module }}" data-action="{{ $log->action }}" data-date="{{ $log->created_at->format('Y-m-d') }}">
                        <td class="tm-muted small" data-order="{{ $log->created_at->timestamp }}">{{ $log->created_at->format('j M Y, g:i A') }}</td>
                        <td class="tm-muted small">{{ $log->user->name ?? 'System' }}</td>
                        <td>
                            <span class="badge rounded-pill text-bg-{{ $actionColors[$log->action] ?? 'secondary' }} fw-normal px-3 py-2">{{ $log->action }}</span>
                        </td>
                        <td>
                            @if ($log->record_url)
                                <a href="{{ $log->record_url }}" class="text-decoration-none u-c610b8">
                                    <span class="tm-muted small d-block">{{ $log->module }}</span>
                                    <span class="fw-semibold small">{{ $log->record_label }}</span>
                                </a>
                            @else
                                <span class="tm-muted small d-block">{{ $log->module }}</span>
                                <span class="fw-semibold small">{{ $log->record_label ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="small">
                            @if ($arrowParts)
                                <span class="text-decoration-line-through tm-muted">{{ $arrowParts[0] }}</span>
                                → <span class="fw-semibold">{{ $arrowParts[1] }}</span>
                            @else
                                {{ $details ?? '—' }}
                            @endif
                        </td>
                        <td class="tm-muted small">{{ $log->device() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
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

    function auditLogWithinRange(dateStr, from, to) {
        if (from && dateStr < from) {
            return false;
        }
        if (to && dateStr > to) {
            return false;
        }
        return true;
    }

    $(function () {
        var table = $('#auditLogTable').DataTable({
            dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center px-3 py-3"i<"d-flex align-items-center gap-3"p>>',
            pageLength: 20,
            autoWidth: false,
            searching: true,
            order: [[0, 'desc']],
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 of 0 entries',
                paginate: { previous: '‹', next: '›' },
            },
            columnDefs: [
                {
                    targets: '_all',
                    render: function (data, type) {
                        if (type === 'filter' || type === 'sort') {
                            return stripHtml(data);
                        }
                        return data;
                    },
                },
            ],
        });

        $.fn.dataTable.ext.search.push(function (settings, data, index) {
            if (settings.nTable.id !== 'auditLogTable') {
                return true;
            }

            var row = $(table.row(index).node());
            var dateFrom = $('#auditLogDateFrom').val();
            var dateTo = $('#auditLogDateTo').val();
            var query = $('#auditLogSearch').val().trim().toLowerCase();

            if (!auditLogWithinRange(row.data('date'), dateFrom, dateTo)) {
                return false;
            }
            if (query) {
                // data holds the rendered filter-text for every column: Date & Time,
                // User, Action, Record, Details, Device (in that column order).
                var matchesQuery = data.slice(0, 6).some(function (cell) {
                    return (cell || '').toLowerCase().indexOf(query) !== -1;
                });
                if (!matchesQuery) {
                    return false;
                }
            }

            return true;
        });

        $('#auditLogSearch').on('keyup input', function () {
            table.draw();
        });

        var fyStartYear = moment().month() >= 3 ? moment().year() : moment().year() - 1;
        var fyStart = moment([fyStartYear, 3, 1]);
        var fyEnd = moment([fyStartYear + 1, 2, 31]);

        $('#auditLogDateRangeBtn').daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            locale: { format: 'DD MMM YYYY', cancelLabel: 'Cancel' },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 days': [moment().subtract(6, 'days'), moment()],
                'This month': [moment().startOf('month'), moment().endOf('month')],
                'Last month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This financial year': [fyStart, fyEnd],
            },
        }, function (start, end) {
            $('#auditLogDateFrom').val(start.format('YYYY-MM-DD'));
            $('#auditLogDateTo').val(end.format('YYYY-MM-DD'));
            $('#auditLogDateRangeLabel').text(start.format('D MMM') + ' – ' + end.format('D MMM YYYY'));
            table.draw();
        });
    });
</script>
@endpush
