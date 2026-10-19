@extends('layouts.app')

@section('title', 'Reports — ' . config('app.name', 'Task Management'))


@php
    $money = fn ($amount) => '₹'.number_format((float) $amount);
    $active = $definitions[$report];
    $has = fn (string $field) => in_array($field, $active['filters'], true);
    $selectedYear = (int) ($filters['fy'] ?? 0);
@endphp

@section('content')
<x-page-header title="Reports" subtitle="See how the firm is doing and export any report" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Reports']]" />

<div class="report-filter-card p-4 mb-3">
    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
        <input type="hidden" name="report" value="{{ $report }}">

        <div class="col-auto">
            <span class="report-field-label">Report</span>
            <div class="dropdown">
                <button type="button" class="report-picker d-flex align-items-center justify-content-between gap-3 w-100" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>{{ $active['label'] }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="dropdown-menu report-menu">
                    @foreach ($groups as $group => $keys)
                        <div class="report-menu-group">{{ $group }}</div>
                        @foreach ($keys as $key)
                            <a href="{{ route('reports.index', ['report' => $key]) }}" class="report-menu-item {{ $key === $report ? 'active' : '' }}">
                                <span>
                                    <span class="name d-block">{{ $definitions[$key]['label'] }}</span>
                                    <span class="desc d-block">{{ $definitions[$key]['description'] }}</span>
                                </span>
                                @if ($key === $report)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1d4fb8" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                @endif
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        @if ($has('client_id'))
            <div class="col u-351b3c">
                <label class="report-field-label" for="rClient">Client</label>
                <select id="rClient" name="client_id" class="form-select tm-field">
                    <option value="">Choose a client…</option>
                    @foreach ($options['clients'] as $client)
                        <option value="{{ $client->id }}" @selected((string) ($filters['client_id'] ?? '') === (string) $client->id)>{{ $client->name }} · {{ $client->phone }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('fy'))
            <div class="col u-351b3c">
                <label class="report-field-label" for="rFy">Financial year</label>
                <select id="rFy" name="fy" class="form-select tm-field">
                    @foreach ($options['years'] as $year => $label)
                        <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('from'))
            <div class="col-auto">
                <label class="report-field-label" for="rFrom">From</label>
                <input type="date" id="rFrom" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control tm-field">
            </div>
            <div class="col-auto">
                <label class="report-field-label" for="rTo">To</label>
                <input type="date" id="rTo" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control tm-field">
            </div>
        @endif

        @if ($has('as_on'))
            <div class="col-auto">
                <label class="report-field-label" for="rAsOn">As on date</label>
                <input type="date" id="rAsOn" name="as_on" value="{{ $filters['as_on'] ?? '' }}" class="form-control tm-field">
            </div>
        @endif

        @if ($has('status'))
            <div class="col-auto">
                <label class="report-field-label" for="rStatus">Status</label>
                <select id="rStatus" name="status" class="form-select tm-field">
                    <option value="">All statuses</option>
                    @foreach ($options['statuses'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('enquiry_status'))
            <div class="col-auto">
                <label class="report-field-label" for="rEnqStatus">Status</label>
                <select id="rEnqStatus" name="enquiry_status" class="form-select tm-field">
                    <option value="">All</option>
                    @foreach (['Open', 'Closed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['enquiry_status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('service_id'))
            <div class="col-auto">
                <label class="report-field-label" for="rService">Service</label>
                <select id="rService" name="service_id" class="form-select tm-field">
                    <option value="">All services</option>
                    @foreach ($options['services'] as $service)
                        <option value="{{ $service->id }}" @selected((string) ($filters['service_id'] ?? '') === (string) $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('staff_id'))
            <div class="col-auto">
                <label class="report-field-label" for="rStaff">Staff</label>
                <select id="rStaff" name="staff_id" class="form-select tm-field">
                    <option value="">All staff</option>
                    @foreach ($options['staff'] as $person)
                        <option value="{{ $person->id }}" @selected((string) ($filters['staff_id'] ?? '') === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('designation_id'))
            <div class="col-auto">
                <label class="report-field-label" for="rDesignation">Designation</label>
                <select id="rDesignation" name="designation_id" class="form-select tm-field">
                    <option value="">All designations</option>
                    @foreach ($options['designations'] as $designation)
                        <option value="{{ $designation->id }}" @selected((string) ($filters['designation_id'] ?? '') === (string) $designation->id)>{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('created_by'))
            <div class="col-auto">
                <label class="report-field-label" for="rCreatedBy">Created by</label>
                <select id="rCreatedBy" name="created_by" class="form-select tm-field">
                    <option value="">Everyone</option>
                    @foreach ($options['creators'] as $creator)
                        <option value="{{ $creator->id }}" @selected((string) ($filters['created_by'] ?? '') === (string) $creator->id)>{{ $creator->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($has('mode'))
            <div class="col-auto">
                <label class="report-field-label" for="rMode">Mode</label>
                <select id="rMode" name="mode" class="form-select tm-field">
                    <option value="">All modes</option>
                    @foreach ($options['modes'] as $mode)
                        <option value="{{ $mode }}" @selected(($filters['mode'] ?? '') === $mode)>{{ $mode }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-auto">
            <button type="submit" class="btn btn-tm-primary u-603dd1">View report</button>
        </div>
    </form>
</div>

<div class="report-result-card overflow-hidden mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 px-4 py-3">
        <div>
            <h2 class="fw-bold mb-1 u-e3c98a">{{ $result['title'] }}</h2>
            <div class="tm-muted u-5a305f">{{ $result['subtitle'] }}</div>
        </div>
        <div class="d-flex gap-2 report-actions">
            <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-outline-success btn-sm px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Excel
            </a>
        </div>
    </div>

    @if ($result['client'])
        <div class="report-client-strip">
            <strong>{{ $result['client']->name }}</strong>
            <span>{{ $result['client']->phone }}</span>
            @if ($result['client']->email)<span>{{ $result['client']->email }}</span>@endif
            <span class="tm-muted">Client since {{ $result['client']->created_at?->format('M Y') }}</span>
        </div>
    @endif

    @if ($result['note'])
        <div class="text-center tm-muted py-5 u-5a305f">{{ $result['note'] }}</div>
    @elseif ($result['rows']->isEmpty())
        <div class="text-center tm-muted py-5 u-5a305f">Nothing to show for these filters.</div>
    @else
        <div class="table-responsive">
            <table class="table report-table align-middle">
                <thead>
                    <tr>
                        @foreach ($result['columns'] as $column)
                            <th class="{{ ($column['align'] ?? '') === 'end' ? 'text-end' : '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($result['columns'] as $column)
                                @php $cell = $row[$column['key']] ?? null; @endphp
                                <td class="{{ ($column['align'] ?? '') === 'end' ? 'text-end' : '' }}">
                                    @switch ($column['type'])
                                        @case ('strong')<strong>{{ $cell }}</strong>@break
                                        @case ('link')
                                            @if (! empty($cell['url']))<a href="{{ $cell['url'] }}" class="report-link">{{ $cell['text'] }}</a>@else{{ $cell['text'] ?? '—' }}@endif
                                            @break
                                        @case ('money'){{ $money($cell) }}@break
                                        @case ('money_green')<strong class="u-f63532">{{ $money($cell) }}</strong>@break
                                        @case ('money_red')
                                            @if ((float) $cell > 0)<strong class="u-866cfd">{{ $money($cell) }}</strong>@else{{ $money($cell) }}@endif
                                            @break
                                        @case ('green')<strong class="u-f63532">{{ $cell }}</strong>@break
                                        @case ('days')
                                            @if (($cell['value'] ?? null) === null)—@else
                                                <span @style(['font-weight: 700', 'color: #b42318' => $cell['value'] >= ($column['danger_at'] ?? PHP_INT_MAX)])>{{ $cell['value'] }}</span>
                                            @endif
                                            @break
                                        @case ('status')<x-status-badge :status="(string) $cell" style="font-size: .7rem; padding: .4rem .75rem !important;" />@break
                                        @case ('chip')<span class="report-chip {{ $cell['tone'] ?? 'secondary' }}">{{ $cell['text'] }}</span>@break
                                        @default{{ $cell }}
                                    @endswitch
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        @foreach ($result['columns'] as $column)
                            <td class="{{ ($column['align'] ?? '') === 'end' ? 'text-end' : '' }}">
                                @if ($loop->first)
                                    {{ $result['total_label'] }}
                                @elseif (array_key_exists($column['key'], $result['total']))
                                    @php $total = $result['total'][$column['key']]; @endphp
                                    {{ str_starts_with($column['type'], 'money') ? $money($total) : $total }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="report-foot">
            @if ($paged)
                <span>Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} · {{ $rows->perPage() }} rows per page</span>
                @if ($rows->hasPages())
                    {{ $rows->links() }}
                @endif
            @else
                <span>All rows shown</span>
            @endif
        </div>
    @endif
</div>
@endsection
