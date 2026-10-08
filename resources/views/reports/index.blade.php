@extends('layouts.app')

@section('title', 'Reports â€” ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    .report-head { border-bottom: 2px solid #2f9e58; }
    .report-tabs { display:flex; gap:.45rem; overflow-x:auto; padding-bottom:.15rem; }
    .report-tab { white-space:nowrap; border:1px solid #dce2eb; border-radius:2rem; background:#fff; color:#46536a; padding:.62rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; }
    .report-tab.active { background:#141f39; border-color:#141f39; color:#fff; }
    .report-filter, .report-client { background:#fff; border:1px solid #dfe4ec; border-radius:.7rem; }
    .report-card { border:1px solid #e0e5ed; background:#fff; border-radius:.75rem; overflow:hidden; height:100%; }
    .report-card-title { color:#fff; background:#141f39; padding:.8rem 1rem; font-weight:700; font-size:.92rem; }
    .report-card-title.green { background:#2a9d58; }
    .report-card-title.red { background:#b9271d; }
    .report-card-title.blue { background:#24569c; }
    .report-card-title.purple { background:#6042b5; }
    .report-stat { color:#fff; position:relative; overflow:hidden; min-height:108px; border-radius:.7rem; padding:1rem; box-shadow:0 8px 18px #17243b22; }
    .report-stat:after,.report-stat:before { content:""; position:absolute; border-radius:50%; background:#ffffff1c; width:90px;height:90px;right:-18px;bottom:-40px; }
    .report-stat:before { width:56px;height:56px;right:24px;bottom:-19px; }
    .report-stat-label,.report-stat-value,.report-stat-caption { position:relative; z-index:1; }
    .report-stat-label,.report-stat-caption { font-size:.77rem; color:#ffffffd1; font-weight:600; }
    .report-stat-value { font-size:1.45rem; font-weight:800; margin:.25rem 0; }
    .report-progress { height:9px; background:#edf0f3; border-radius:2rem; overflow:hidden; }
    .report-progress span { display:block;height:100%;border-radius:2rem; }
    .report-table { font-size:.8rem; margin:0; }
    .report-table thead th { background:#141f39; color:#e4e9f2; text-transform:uppercase; font-size:.67rem; letter-spacing:.04em; padding:.75rem .85rem; white-space:nowrap; border-color:#39445a; }
    .report-table tbody td { padding:.8rem .85rem; vertical-align:middle; }
    .report-table tbody tr:nth-child(even) { background:#f8f9fb; }
    .report-badge { display:inline-block; border-radius:1rem; padding:.32rem .6rem; background:#eef0f3; color:#475366; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .report-chart { display:flex; align-items:flex-end; justify-content:space-around; gap:1rem; height:235px; padding:1.2rem .6rem .2rem; border-bottom:1px solid #e8edf3; background:repeating-linear-gradient(to bottom,transparent 0,transparent 24%,#eef1f5 24.5%,transparent 25%); }
    .report-month { display:flex; align-items:flex-end; justify-content:center; gap:5px; height:100%; flex:1; position:relative; }
    .report-bar { width:22px; min-height:3px; border-radius:3px 3px 0 0; background:#c9d6ef; }
    .report-bar.received { background:#2a9d58; }
    .report-month-label { position:absolute; bottom:-1.4rem; font-size:.72rem; color:#67758b; }
    @media(max-width:767.98px) { .report-stat-value{font-size:1.2rem}.report-chart{height:180px}.report-table{min-width:720px} }
    @media print { .tm-sidebar,.tm-topbar,.report-actions,.report-tabs,.report-filter{display:none!important} main{padding:0!important}.report-card,.report-stat{break-inside:avoid} }
</style>
@endpush

@section('content')
@php
    $tabLabels = [
        'revenue' => 'Revenue',
        'payments' => 'Payments',
        'staff-performance' => 'Staff performance',
        'client-history' => 'Client history',
        'open-tickets' => 'Open tickets',
        'pending-documents' => 'Pending documents',
        'gst-summary' => 'GST summary',
    ];
    $money = fn ($amount) => 'â‚¹'.number_format((float) $amount);
    $statusColors = ['Documents Pending' => '#6c757d', 'Partially Received' => '#495057', 'Documents Received' => '#2f5fbe', 'Under Verification' => '#564bc7', 'Additional Documents Required' => '#c87500', 'Work In Progress' => '#b18700', 'Submitted to Department' => '#8045ba', 'On Hold' => '#bd302d', 'Task Completed' => '#2a9d58'];
    $statCards = match ($tab) {
        'payments' => [
            ['Received', $money($paid), 'in selected period', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['Pending', $money($pending), $tickets->filter(fn ($ticket) => $ticket->balanceDue() > 0)->count().' tickets', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
            ['Average ticket fee', $money($tickets->count() ? $billed / $tickets->count() : 0), 'billed amount per ticket', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Partial payments', (string) $payments->where('is_partial', true)->count(), 'payments in selection', 'linear-gradient(135deg,#583500,#99620c)'],
        ],
        'client-history' => [
            ['Total tickets', (string) $clientTickets->count(), 'for selected client', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Fees billed', $money($clientTickets->sum('total')), 'in selected period', 'linear-gradient(135deg,#541747,#7b2869)'],
            ['Fees paid', $money($clientTickets->pluck('enquiry')->filter()->unique('id')->sum(fn ($enquiry) => $enquiry->amountPaid())), 'across selected tickets', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['Pending', $money($clientTickets->pluck('enquiry')->filter()->unique('id')->sum(fn ($enquiry) => $enquiry->balanceDue())), 'outstanding balance', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
        ],
        'open-tickets' => [
            ['Open tickets', (string) $openTickets->count(), 'not yet closed', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Waiting for client', (string) $statusCounts['Documents Pending'], 'documents pending', 'linear-gradient(135deg,#583500,#99620c)'],
            ['Open over 30 days', (string) $openTickets->filter(fn ($ticket) => $ticket->created_at?->diffInDays(now()) > 30)->count(), 'need follow-up', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
            ['Ready for payment', (string) $tickets->filter(fn ($ticket) => $ticket->status?->value === 'Task Completed' && $ticket->balanceDue() > 0)->count(), 'task completed, balance due', 'linear-gradient(135deg,#164f29,#2e8747)'],
        ],
        'pending-documents' => [
            ['Pending documents', (string) $pendingDocuments->count(), 'awaiting review', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Affected tickets', (string) $pendingDocuments->pluck('ticket.id')->unique()->count(), 'with documents to check', 'linear-gradient(135deg,#583500,#99620c)'],
            ['Open tickets', (string) $openTickets->count(), 'currently in progress', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['Unassigned', (string) $openTickets->whereNull('assigned_to')->count(), 'need staff assignment', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
        ],
        'gst-summary' => [
            ['GST billed', $money($tickets->sum('gst_amount')), 'selected ticket period', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['GST received', $money($gstPaid), 'allocated by payment share', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['GST pending', $money($gstPending), 'remaining GST on ticket balances', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
            ['Taxable value', $money($tickets->sum('price')), 'before GST', 'linear-gradient(135deg,#541747,#7b2869)'],
        ],
        'staff-performance' => [
            ['Tickets', (string) $tickets->count(), 'in selected period', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Completed', (string) $tickets->filter(fn ($ticket) => $ticket->status?->isClosed())->count(), 'closed tickets', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['Fees received', $money($paid), 'from selected tickets', 'linear-gradient(135deg,#541747,#7b2869)'],
            ['Open tickets', (string) $openTickets->count(), 'assigned work', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
        ],
        default => [
            ['Fees received', $money($paid), 'in selected period', 'linear-gradient(135deg,#164f29,#2e8747)'],
            ['Fees billed', $money($billed), $tickets->count().' tickets', 'linear-gradient(135deg,#123c80,#2457b5)'],
            ['Still to collect', $money($pending), 'across selected tickets', 'linear-gradient(135deg,#4a1010,#8b1d1d)'],
            ['Collection rate', ($billed > 0 ? (int) round($paid / $billed * 100) : 0).'%', 'received of billed', 'linear-gradient(135deg,#541747,#7b2869)'],
        ],
    };
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Reports']]" />
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-3 report-head">
    <div>
        <h1 class="fw-bold mb-1" style="font-size: 1.15rem;">Reports</h1>
        <p class="text-secondary mb-0" style="font-size: .8rem;">See how the firm is doing and export any report</p>
    </div>
    <div class="d-flex gap-2 report-actions">
        <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-outline-success btn-sm px-3 py-2">â†“ Export Excel</a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm px-3 py-2">â†“ Export PDF</button>
    </div>
</div>

<nav class="report-tabs mb-3" aria-label="Report type">
    @foreach ($tabLabels as $key => $label)
        <a class="report-tab {{ $tab === $key ? 'active' : '' }}" href="{{ route('reports.index', array_merge(request()->query(), ['tab' => $key])) }}">{{ $label }}</a>
    @endforeach
</nav>

<form method="GET" action="{{ route('reports.index') }}" class="report-filter p-3 mb-3 d-flex flex-wrap align-items-center gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm" aria-label="From date" style="width:auto">
    <span class="text-secondary small">to</span>
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm" aria-label="To date" style="width:auto">
    <select name="service_id" class="form-select form-select-sm" style="width:auto">
        <option value="">Service: All</option>
        @foreach ($services as $service)<option value="{{ $service->id }}" @selected(($filters['service_id'] ?? '') == $service->id)>{{ $service->name }}</option>@endforeach
    </select>
    @if (in_array($tab, ['revenue','staff-performance','open-tickets']))
        <select name="staff_id" class="form-select form-select-sm" style="width:auto">
            <option value="">Staff: All</option>
            @foreach ($staff as $person)<option value="{{ $person->id }}" @selected(($filters['staff_id'] ?? '') == $person->id)>{{ $person->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">Status: All</option>
            @foreach (\App\Enums\TicketStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>@endforeach
        </select>
    @elseif ($tab === 'payments')
        <select name="mode" class="form-select form-select-sm" style="width:auto">
            <option value="">Mode: All</option>
            @foreach ($payments->pluck('mode')->unique() as $mode)<option value="{{ $mode }}" @selected(($filters['mode'] ?? '') === $mode)>{{ $mode }}</option>@endforeach
        </select>
        <select name="received_by" class="form-select form-select-sm" style="width:auto">
            <option value="">Received by: All</option>
            @foreach ($staff as $person)<option value="{{ $person->id }}" @selected(($filters['received_by'] ?? '') == $person->id)>{{ $person->name }}</option>@endforeach
        </select>
    @elseif ($tab === 'client-history')
        <select name="client_id" class="form-select form-select-sm" style="width:auto">
            <option value="">Choose client</option>
            @foreach ($clients as $client)<option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->name }}</option>@endforeach
        </select>
    @endif
    <span class="flex-grow-1"></span>
    <button class="btn btn-success btn-sm px-3">Apply</button>
    <a href="{{ route('reports.index', ['tab' => $tab]) }}" class="btn btn-link btn-sm text-secondary text-decoration-none">Reset</a>
</form>

@if ($tab === 'client-history')
    <div class="report-client mb-3 p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        @if ($selectedClient)
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:46px;height:46px;background:#141f39">{{ strtoupper(substr($selectedClient->name, 0, 2)) }}</div>
                <div><div class="fw-bold">{{ $selectedClient->name }}</div><div class="text-secondary small">{{ $selectedClient->phone }} Â· {{ $selectedClient->email }} Â· Client since {{ $selectedClient->created_at?->format('M Y') }}</div></div>
            </div>
        @else
            <div class="fw-semibold">Choose a client to view their ticket and payment history.</div>
        @endif
    </div>
@endif

<div class="row g-3 mb-3">
    @foreach ($statCards as [$label, $value, $caption, $gradient])
        <div class="col-6 col-xl-3"><div class="report-stat" style="background:{{ $gradient }}"><div class="report-stat-label">{{ $label }}</div><div class="report-stat-value">{{ $value }}</div><div class="report-stat-caption">{{ $caption }}</div></div></div>
    @endforeach
</div>

@if ($tab === 'revenue')
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <section class="report-card">
                <div class="report-card-title d-flex justify-content-between"><span>Fees by month</span><span class="small fw-normal">Billed &nbsp; Received</span></div>
                <div class="p-3"><div class="report-chart">
                    @php $monthMax = max(1, $months->max(fn ($month) => max($month['billed'], $month['received']))); @endphp
                    @foreach ($months as $month)
                        <div class="report-month" title="{{ $month['label'] }}: {{ $money($month['billed']) }} billed, {{ $money($month['received']) }} received">
                            <span class="report-bar" style="height:{{ max(2, $month['billed'] / $monthMax * 88) }}%"></span>
                            <span class="report-bar received" style="height:{{ max(2, $month['received'] / $monthMax * 88) }}%"></span>
                            <span class="report-month-label">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div><div class="d-flex gap-3 justify-content-center mt-4 small"><span><i class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#c9d6ef"></i>Billed</span><span><i class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#2a9d58"></i>Received</span></div></div>
            </section>
        </div>
        <div class="col-12 col-xl-5">
            <section class="report-card"><div class="report-card-title green">Received by service</div><div class="p-3">
                @forelse ($serviceBreakdown as $row)
                    @php $share = $serviceBreakdown->max('received') > 0 ? $row['received'] / $serviceBreakdown->max('received') * 100 : 0; @endphp
                    <div class="mb-3"><div class="d-flex justify-content-between gap-2 small fw-semibold mb-1"><span>{{ $row['service'] }}</span><span>{{ $money($row['received']) }}</span></div><div class="report-progress"><span style="width:{{ $share }}%;background:#24569c"></span></div></div>
                @empty <p class="text-secondary small mb-0">No ticket revenue in this period.</p> @endforelse
            </div></section>
        </div>
    </div>
    @include('reports.partials.service-breakdown')
@elseif ($tab === 'payments')
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6"><section class="report-card"><div class="report-card-title red">Pending fees by age</div><div class="p-3">
            @php
                $ageGroups = [['0â€“15 days', 0], ['16â€“30 days', 0], ['31â€“60 days', 0], ['Over 60 days', 0]];
                foreach ($tickets->groupBy('enquiry_id') as $enquiryTickets) {
                    $enquiry = $enquiryTickets->first()->enquiry;
                    if (! $enquiry || $enquiry->balanceDue() <= 0) {
                        continue;
                    }
                    $age = $enquiryTickets->min('created_at')?->diffInDays(now()) ?? 0;
                    $idx = $age <= 15 ? 0 : ($age <= 30 ? 1 : ($age <= 60 ? 2 : 3));
                    $ageGroups[$idx][1] += $enquiry->balanceDue();
                }
                $ageMax = max(1, max(array_column($ageGroups, 1)));
            @endphp
            @foreach ($ageGroups as [$label, $amount])<div class="mb-3"><div class="d-flex justify-content-between small fw-semibold mb-1"><span>{{ $label }}</span><span>{{ $money($amount) }}</span></div><div class="report-progress"><span style="width:{{ $amount / $ageMax * 100 }}%;background:#2a9d58"></span></div></div>@endforeach
        </div></section></div>
        <div class="col-12 col-xl-6"><section class="report-card"><div class="report-card-title green">Received by payment mode</div><div class="p-3">
            @php $byMode = $payments->groupBy('mode')->map(fn ($rows) => $rows->sum('amount')); $modeMax = max(1, (float) $byMode->max()); @endphp
            @forelse ($byMode as $mode => $amount)<div class="mb-3"><div class="d-flex justify-content-between small fw-semibold mb-1"><span>{{ $mode }}</span><span>{{ $money($amount) }}</span></div><div class="report-progress"><span style="width:{{ $amount / $modeMax * 100 }}%;background:#6042b5"></span></div></div>@empty <p class="text-secondary small">No payments in this period.</p>@endforelse
        </div></section></div>
    </div>
    <section class="report-card"><div class="report-card-title">Payments received</div><div class="table-responsive"><table class="table report-table"><thead><tr><th>Date</th><th>Client</th><th>Enquiry</th><th>Mode</th><th>Received by</th><th class="text-end">Amount</th></tr></thead><tbody>
        @forelse ($payments as $payment)<tr><td>{{ $payment->paid_at?->format('j M Y') }}</td><td>{{ $payment->enquiry?->customer?->name ?? 'â€”' }}</td><td><a href="{{ route('enquiries.show', $payment->enquiry) }}">{{ $payment->enquiry?->number }}</a><div class="text-secondary small">{{ $payment->enquiry?->tickets->pluck('service.name')->filter()->implode(', ') }}</div></td><td>{{ $payment->mode }}</td><td>{{ $payment->receivedBy?->name ?? 'â€”' }}</td><td class="text-end fw-bold text-success">{{ $money($payment->amount) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary p-4">No payments match these filters.</td></tr>@endforelse
    </tbody></table></div></section>
@elseif ($tab === 'client-history')
    <section class="report-card mb-3"><div class="report-card-title">All tickets for this client</div><div class="table-responsive"><table class="table report-table"><thead><tr><th>Date</th><th>Ticket</th><th>Staff</th><th>Status</th><th class="text-end">Fee</th><th class="text-end">Paid</th></tr></thead><tbody>
        @forelse ($clientTickets as $ticket)<tr><td>{{ $ticket->created_at?->format('j M Y') }}</td><td><a href="{{ route('tickets.show', $ticket) }}" class="fw-bold">{{ $ticket->number }}</a><div class="text-secondary small">{{ $ticket->service?->name }}</div></td><td>{{ $ticket->assignedTo?->name ?? 'â€”' }}</td><td><span class="report-badge">{{ $ticket->status?->value }}</span></td><td class="text-end fw-bold">{{ $money($ticket->total) }}</td><td class="text-end fw-bold text-success">{{ $money($ticket->amountPaid()) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary p-4">Select a client with matching tickets.</td></tr>@endforelse
    </tbody></table></div></section>
@elseif ($tab === 'open-tickets')
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7"><section class="report-card"><div class="report-card-title">Open tickets by status</div><div class="p-3">
            @foreach ($statusCounts as $status => $count)
                @php $maxCount = max(1, $statusCounts->max()); @endphp
                <div class="mb-2"><div class="d-flex justify-content-between small fw-semibold mb-1"><span>{{ $status }}</span><span>{{ $count }}</span></div><div class="report-progress"><span style="width:{{ $count / $maxCount * 100 }}%;background:{{ $statusColors[$status] ?? '#24569c' }}"></span></div></div>
            @endforeach
        </div></section></div>
        <div class="col-12 col-xl-5"><section class="report-card"><div class="report-card-title red">How long they have been open</div><div class="p-3">
            @php $openGroups = [['0â€“7 days',0],['8â€“15 days',0],['16â€“30 days',0],['Over 30 days',0]]; foreach ($openTickets as $ticket) { $days=$ticket->created_at?->diffInDays(now()) ?? 0; $idx=$days<=7?0:($days<=15?1:($days<=30?2:3)); $openGroups[$idx][1]++; } $openMax=max(1,max(array_column($openGroups,1))); @endphp
            @foreach ($openGroups as [$label,$count])<div class="mb-3"><div class="d-flex justify-content-between small fw-semibold mb-1"><span>{{ $label }}</span><span>{{ $count }}</span></div><div class="report-progress"><span style="width:{{ $count / $openMax * 100 }}%;background:#2a9d58"></span></div></div>@endforeach
        </div></section></div>
    </div>
    @include('reports.partials.open-tickets')
@elseif ($tab === 'pending-documents')
    <section class="report-card"><div class="report-card-title">Documents awaiting review</div><div class="table-responsive"><table class="table report-table"><thead><tr><th>Ticket</th><th>Client</th><th>Document</th><th>Version</th><th>Uploaded by</th><th>Uploaded</th><th>Status</th></tr></thead><tbody>
        @forelse ($pendingDocuments as $row)<tr><td><a href="{{ route('tickets.show', $row['ticket']) }}">{{ $row['ticket']->number }}</a></td><td>{{ $row['ticket']->customer?->name }}</td><td>{{ $row['document']->original_filename }}</td><td>v{{ $row['document']->version }}</td><td>{{ $row['document']->uploadedBy?->name ?? 'â€”' }}</td><td>{{ $row['document']->created_at?->format('j M Y') }}</td><td><span class="report-badge">Awaiting check</span></td></tr>@empty<tr><td colspan="7" class="text-center text-secondary p-4">No documents are awaiting review.</td></tr>@endforelse
    </tbody></table></div></section>
@elseif ($tab === 'gst-summary')
    <section class="report-card"><div class="report-card-title">GST service-wise breakdown</div><div class="table-responsive"><table class="table report-table"><thead><tr><th>Service</th><th class="text-end">Tickets</th><th class="text-end">Taxable value</th><th class="text-end">GST billed</th><th class="text-end">GST rate</th></tr></thead><tbody>
        @forelse ($serviceBreakdown as $row)<tr><td class="fw-bold">{{ $row['service'] }}</td><td class="text-end">{{ $row['tickets'] }}</td><td class="text-end">{{ $money($tickets->where('service_id', $row['service_id'])->sum('price')) }}</td><td class="text-end fw-bold">{{ $money($row['gst']) }}</td><td class="text-end">{{ number_format((float) ($tickets->where('service_id', $row['service_id'])->avg('gst_percent') ?? 0), 0) }}%</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary p-4">No GST data for these filters.</td></tr>@endforelse
    </tbody></table></div></section>
@elseif ($tab === 'staff-performance')
    <section class="report-card"><div class="report-card-title">Staff performance</div><div class="table-responsive"><table class="table report-table"><thead><tr><th>Staff</th><th class="text-end">Open</th><th class="text-end">Completed</th><th class="text-end">Fees billed</th><th class="text-end">Fees received</th></tr></thead><tbody>
        @forelse ($staff->when(auth()->user()->isEmployee(), fn ($rows) => $rows->where('id', auth()->id())) as $person)
            @php $assigned = $tickets->where('assigned_to', $person->id); @endphp
            <tr><td class="fw-bold">{{ $person->name }}</td><td class="text-end">{{ $assigned->filter(fn ($ticket) => ! $ticket->status?->isClosed())->count() }}</td><td class="text-end">{{ $assigned->filter(fn ($ticket) => $ticket->status?->isClosed())->count() }}</td><td class="text-end">{{ $money($assigned->sum('total')) }}</td><td class="text-end text-success fw-bold">{{ $money($assigned->pluck('enquiry')->filter()->unique('id')->sum(fn ($enquiry) => $enquiry->amountPaid())) }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-secondary p-4">No staff tickets match these filters.</td></tr>@endforelse
    </tbody></table></div></section>
@endif
@endsection
