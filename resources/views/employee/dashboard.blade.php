@extends('layouts.app')

@section('title', 'Employee Dashboard — ' . config('app.name', 'Task Management'))

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $employeeName = auth()->user()->name;
    $firstName = explode(' ', $employeeName)[0];
    $currency = fn (float $amount): string => '₹'.number_format($amount, 0);
@endphp

@section('content')
<x-page-header
    :title="$greeting . ', ' . $firstName"
    :subtitle="'Here is your work overview for ' . now()->format('l, j F Y')"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    <x-slot:actions>
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary">+ New Enquiry</a>
    </x-slot:actions>
</x-page-header>

@php
    $cardModalIds = ['dashOpenTicketsModal', 'dashAwaitingActionModal', 'dashCompletedThisMonthModal', 'dashDocumentsToVerifyModal'];
@endphp

<div class="row g-3 mb-4">
    @foreach ($statCards as $i => $card)
        <div class="col-6 col-xl-3">
            <div
                class="tm-stat-card p-3 h-100 text-white position-relative"
                style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden; cursor: pointer;"
                data-bs-toggle="modal"
                data-bs-target="#{{ $cardModalIds[$i] }}"
            >
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white js-count-up" data-count="{{ $card['count'] }}">{{ $card['count'] }}</div>
                    <div class="small" style="color: {{ $card['trendColor'] }};">{{ $card['trend'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-8">
        <section class="tm-card tm-card-hover p-0 h-100" style="overflow:hidden">
            <div class="d-flex align-items-center justify-content-between p-3" style="background:#101b3d">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">My ticket activity</h2>
                <span class="small text-white-50">Last 6 months</span>
            </div>
            <div class="p-3">
                <div style="height:260px"><canvas id="employeeTicketActivity"></canvas></div>
                <div class="d-flex justify-content-center gap-3 small mt-2">
                    <span><i class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#2f5fbe"></i>Opened</span>
                    <span><i class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#4a9b3e"></i>Completed</span>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="tm-card tm-card-hover p-0 h-100" style="overflow:hidden">
            <div class="p-3" style="background:#101b3d">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">My tickets by status</h2>
            </div>
            <div class="p-3">
                @if ($statusBreakdown)
                    <div class="position-relative mx-auto" style="height:220px;max-width:280px">
                        <canvas id="employeeTicketStatuses"></canvas>
                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                            <div class="h3 tm-serif fw-bold mb-0">{{ $openTicketsTotal }}</div>
                            <div class="small text-secondary">open tickets</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                        @foreach ($statusBreakdown as $status)
                            <span class="small d-flex align-items-center gap-1">
                                <i class="d-inline-block rounded-circle" style="width:8px;height:8px;background:{{ $status['color'] }}"></i>
                                {{ $status['label'] }} <strong>{{ $status['count'] }}</strong>
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-secondary py-5">No open tickets right now.</div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-6">
        <section class="tm-card tm-card-hover p-0 h-100" style="overflow:hidden">
            <div class="p-3" style="background:#101b3d">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Fees received from my tickets</h2>
            </div>
            <div class="p-3">
                <div style="height:230px"><canvas id="employeeFeesByMonth"></canvas></div>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-6">
        <section class="tm-card tm-card-hover p-0 h-100" style="overflow:hidden">
            <div class="d-flex align-items-center justify-content-between p-3" style="background:#101b3d">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Tickets by service</h2>
                <div class="d-flex align-items-center gap-2 small text-white">
                    <span><i class="d-inline-block rounded-1 me-1" style="width:9px;height:9px;background:#0d6efd"></i>Open</span>
                    <span><i class="d-inline-block rounded-1 me-1" style="width:9px;height:9px;background:#4a9b3e"></i>Completed</span>
                </div>
            </div>
            <div class="p-3">
                @forelse ($ticketsByService as $row)
                    @php $rowTotal = max(1, $row['open'] + $row['completed']); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold">{{ $row['name'] }}</span>
                            <span class="text-secondary">{{ $row['open'] + $row['completed'] }}</span>
                        </div>
                        <div class="progress" style="height:9px;background:#e5e7ec">
                            <div class="progress-bar" role="progressbar" style="width:{{ (int) round($row['open'] / $rowTotal * 100) }}%;background:#0d6efd" aria-valuenow="{{ $row['open'] }}" aria-valuemin="0" aria-valuemax="{{ $rowTotal }}"></div>
                            <div class="progress-bar" role="progressbar" style="width:{{ (int) round($row['completed'] / $rowTotal * 100) }}%;background:#4a9b3e" aria-valuenow="{{ $row['completed'] }}" aria-valuemin="0" aria-valuemax="{{ $rowTotal }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-secondary py-5">Your service workload will appear here.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>

<section class="tm-card p-0" style="overflow:hidden">
    <div class="d-flex align-items-center justify-content-between p-3 pb-2">
        <div>
            <h2 class="h6 tm-serif fw-bold mb-1">My recent tickets</h2>
            <p class="small text-secondary mb-0">Your five most recently assigned tickets</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="small">View all tickets</a>
    </div>
    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0">
            <thead>
                <tr><th>Ticket</th><th>Client</th><th>Service</th><th>Status</th><th class="text-end">Fee</th></tr>
            </thead>
            <tbody>
                @forelse ($myTickets as $ticket)
                    <tr>
                        <td class="fw-semibold"><a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none">{{ $ticket->number }}</a></td>
                        <td>{{ $ticket->customer?->name ?? '—' }}</td>
                        <td>{{ $ticket->service?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$ticket->status?->value ?? 'Unknown'" /></td>
                        <td class="text-end fw-semibold">{{ $currency((float) $ticket->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No tickets are assigned to you yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="dashOpenTicketsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <div class="p-3" style="background: #0a4fc4;">
                <h2 class="h6 fw-bold mb-0 text-white">My open tickets ({{ $openTicketsList->count() }})</h2>
            </div>
            <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Ticket</th><th>Client</th><th>Service</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($openTicketsList as $ticket)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none">{{ $ticket->number }}</a></td>
                                <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                <td>{{ $ticket->service?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$ticket->status?->value ?? 'Unknown'" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">No open tickets right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashAwaitingActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <div class="p-3" style="background: #7f1616;">
                <h2 class="h6 fw-bold mb-0 text-white">Awaiting my action ({{ $awaitingActionList->count() }})</h2>
            </div>
            <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Ticket</th><th>Client</th><th>Service</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($awaitingActionList as $ticket)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none">{{ $ticket->number }}</a></td>
                                <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                <td>{{ $ticket->service?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$ticket->status?->value ?? 'Unknown'" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">Nothing is waiting on you right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashCompletedThisMonthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <div class="p-3" style="background: #1f6b30;">
                <h2 class="h6 fw-bold mb-0 text-white">Completed this month ({{ $completedThisMonthList->count() }})</h2>
            </div>
            <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Ticket</th><th>Client</th><th>Service</th><th>Status</th><th>Completed</th></tr></thead>
                    <tbody>
                        @forelse ($completedThisMonthList as $ticket)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none">{{ $ticket->number }}</a></td>
                                <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                <td>{{ $ticket->service?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$ticket->status?->value ?? 'Unknown'" /></td>
                                <td class="tm-muted">{{ $ticket->completed_at?->format('j M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Nothing completed this month yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashDocumentsToVerifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <div class="p-3" style="background: #6e1d58;">
                <h2 class="h6 fw-bold mb-0 text-white">Documents to verify ({{ $documentsToVerifyList->count() }})</h2>
            </div>
            <div class="modal-body p-0" style="max-height: 60vh; overflow-y: auto;">
                <table class="table tm-table align-middle mb-0">
                    <thead><tr><th>Ticket</th><th>Client</th><th>Document</th><th>Uploaded</th></tr></thead>
                    <tbody>
                        @forelse ($documentsToVerifyList as $row)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('tickets.show', $row['ticket']) }}" class="text-decoration-none">{{ $row['ticket']->number }}</a></td>
                                <td>{{ $row['ticket']->customer?->name ?? '—' }}</td>
                                <td>{{ $row['document']->serviceDocument?->name ?? '—' }}</td>
                                <td class="tm-muted">{{ $row['document']->created_at->format('j M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">No documents awaiting verification.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        document.querySelectorAll('.js-count-up').forEach(function (element) {
            var target = Number(element.dataset.count) || 0;
            var duration = 850;
            var start = null;

            function animate(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = Math.round(eased * target).toLocaleString('en-IN');

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            requestAnimationFrame(animate);
        });

        var chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {display: false},
                tooltip: {backgroundColor: '#101b3d', titleColor: '#fff', bodyColor: '#fff', cornerRadius: 8, padding: 10},
            },
        };
        var activityCanvas = document.getElementById('employeeTicketActivity');
        if (activityCanvas) {
            new Chart(activityCanvas, {
                type: 'line',
                data: {
                    labels: @json($months),
                    datasets: [
                        {label: 'Opened', data: @json($newTickets), borderColor: '#2f5fbe', backgroundColor: 'rgba(47,95,190,.12)', tension: .35, fill: true, pointRadius: 3},
                        {label: 'Completed', data: @json($completedTickets), borderColor: '#4a9b3e', backgroundColor: 'rgba(74,155,62,.10)', tension: .35, fill: true, pointRadius: 3},
                    ],
                },
                options: {...chartOptions, scales: {y: {beginAtZero: true, ticks: {precision: 0}, grid: {color: '#eef0f4'}}, x: {grid: {display: false}}}},
            });
        }

        var statusCanvas = document.getElementById('employeeTicketStatuses');
        if (statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json(array_column($statusBreakdown, 'label')),
                    datasets: [{data: @json(array_column($statusBreakdown, 'count')), backgroundColor: @json(array_column($statusBreakdown, 'color')), borderWidth: 2, borderColor: '#fff'}],
                },
                options: {...chartOptions, cutout: '70%'},
            });
        }

        var feesCanvas = document.getElementById('employeeFeesByMonth');
        if (feesCanvas) {
            new Chart(feesCanvas, {
                type: 'bar',
                data: {
                    labels: @json($months),
                    datasets: [{label: 'Fees received (₹)', data: @json($feesReceived), backgroundColor: '#4a9b3e', borderRadius: 5}],
                },
                options: {...chartOptions, scales: {y: {beginAtZero: true, grid: {color: '#eef0f4'}, ticks: {callback: function (value) { return '₹' + Number(value).toLocaleString('en-IN'); }}}, x: {grid: {display: false}}}},
            });
        }
    })();
</script>
@endpush
@endsection