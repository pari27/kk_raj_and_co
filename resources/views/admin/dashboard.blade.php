@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $fyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
    $fyLabel = 'FY '.$fyStartYear.'-'.substr((string) ($fyStartYear + 1), -2);

    $months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
    $newEnquiries = [18, 22, 20, 27, 21, 24];
    $completedTickets = [15, 19, 18, 25, 20, 22];

    $statusBreakdown = [
        ['label' => 'Documents Pending', 'count' => 14, 'color' => '#9aa1b0'],
        ['label' => 'Documents Received', 'count' => 6, 'color' => '#3b82f6'],
        ['label' => 'Under Verification', 'count' => 5, 'color' => '#6d5bd0'],
        ['label' => 'Additional Docs Required', 'count' => 4, 'color' => '#f97316'],
        ['label' => 'Work In Progress', 'count' => 10, 'color' => '#4a9b3e'],
        ['label' => 'Submitted to Dept.', 'count' => 7, 'color' => '#c026d3'],
        ['label' => 'On Hold', 'count' => 2, 'color' => '#dc3545'],
    ];
    $openTicketsTotal = array_sum(array_column($statusBreakdown, 'count'));

    $feesReceived = [110, 140, 165, 190, 170, 186];
    $feesPending = [28, 24, 28, 45, 24, 42];

    $employeeWorkload = [
        ['name' => 'Priya', 'open' => 14, 'done' => 41],
        ['name' => 'Amit', 'open' => 11, 'done' => 36],
        ['name' => 'Sneha Ghosh', 'open' => 13, 'done' => 32],
        ['name' => 'Rahul Sen', 'open' => 10, 'done' => 26],
    ];

    $ticketsByService = [
        ['name' => 'GST Return Filing', 'completed' => 42, 'open' => 14],
        ['name' => 'GST Registration', 'completed' => 15, 'open' => 6],
        ['name' => 'PAN Card Creation', 'completed' => 14, 'open' => 5],
        ['name' => 'Income Tax Return Filing', 'completed' => 37, 'open' => 11],
        ['name' => 'TDS Return Filing', 'completed' => 18, 'open' => 7],
        ['name' => 'ROC Annual Filing', 'completed' => 9, 'open' => 5],
    ];

    $statCards = [
        ['label' => 'New enquiries (Sep)', 'count' => 27, 'prefix' => '', 'trend' => '+12% vs Aug', 'trendColor' => '#ffffff', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)', 'url' => route('enquiries.index')],
        ['label' => 'Open tickets', 'count' => $openTicketsTotal, 'prefix' => '', 'trend' => '6 more than last week', 'trendColor' => 'rgba(255,255,255,.75)', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)', 'url' => route('tickets.index')],
        ['label' => 'Completed (Sep)', 'count' => 23, 'prefix' => '', 'trend' => '+5% vs Aug', 'trendColor' => '#ffffff', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)', 'url' => route('tickets.index')],
        ['label' => 'Fees pending', 'count' => 42500, 'prefix' => '₹', 'trend' => 'Across 14 clients', 'trendColor' => '#ffffff', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)', 'url' => route('payments.index')],
        ['label' => 'Fees received (Sep)', 'count' => 186000, 'prefix' => '₹', 'trend' => '+18% vs Aug', 'trendColor' => '#ffffff', 'gradient' => 'linear-gradient(135deg, #062a28, #0f766e)', 'url' => route('payments.index')],
    ];
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard']]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="tm-serif fw-bold mb-1" style="font-size: 1.15rem;">Dashboard</h1>
        <p class="tm-muted mb-0" style="font-size: .8rem;">Practice overview &middot; {{ $fyLabel }} &middot; Apr to Sep</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Last 6 months
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item disabled">Other ranges — coming soon</span></li>
            </ul>
        </div>
        <a href="{{ route('enquiries.create') }}" class="btn btn-tm-primary d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            New Enquiry
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl">
            <a href="{{ $card['url'] }}" class="tm-stat-card d-block p-3 h-100 text-white text-decoration-none position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white js-count-up" data-count="{{ $card['count'] }}" data-prefix="{{ $card['prefix'] }}">{{ $card['prefix'] }}0</div>
                    <div class="small" style="color: {{ $card['trendColor'] }};">{{ $card['trend'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="tm-card tm-card-hover p-0 h-100" style="overflow: hidden;">
            <div class="d-flex align-items-start justify-content-between px-3 pt-3 pb-3" style="background: #101b3d;">
                <div>
                    <h2 class="tm-serif fw-bold mb-0 text-white" style="font-size: .88rem;">Enquiries vs completed tickets</h2>
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size: .72rem;">
                    <span class="badge rounded-pill fw-normal" style="border: 1px solid #3b82f6; color: #fff; background: #3b82f6;">New enquiries</span>
                    <span class="badge rounded-pill fw-normal" style="background: #4a9b3e; color: #fff;">Completed</span>
                </div>
            </div>
            <div class="p-3" style="height: 260px;">
                <canvas id="chartEnquiriesVsCompleted"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="tm-card tm-card-hover p-0 h-100" style="overflow: hidden;">
            <div class="p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white" style="font-size: .88rem;">Open tickets by status</h2>
            </div>
            <div class="p-3 d-flex align-items-center gap-3">
                <div style="width: 140px; height: 140px; flex-shrink: 0;">
                    <canvas id="chartStatusBreakdown"></canvas>
                </div>
                <div class="flex-grow-1">
                    @foreach ($statusBreakdown as $row)
                        <div class="d-flex align-items-center justify-content-between mb-2" style="font-size: .8rem;">
                            <span class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width:8px;height:8px;background:{{ $row['color'] }};"></span>
                                {{ $row['label'] }}
                            </span>
                            <span class="fw-semibold">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-5">
        <div class="tm-card tm-card-hover p-0 h-100" style="overflow: hidden;">
            <div class="p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Fees by month</h2>
            </div>
            <div class="p-3" style="height: 220px;">
                <canvas id="chartFeesByMonth"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3">
        <div class="tm-card tm-card-hover p-0 h-100 text-center" style="overflow: hidden;">
            <div class="p-3 text-start" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Fee collection</h2>
            </div>
            <div class="p-3">
                <div class="position-relative mx-auto" style="width: 160px; height: 160px;">
                    <canvas id="chartFeeCollection"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="h3 tm-serif fw-bold mb-0">81%</div>
                    </div>
                </div>
                <div class="mt-2" style="font-size: .78rem;">₹9,79,000 of ₹12,08,000 collected</div>
                <div style="font-size: .78rem; color: #d97706;">₹2,29,000 still to collect</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card tm-card-hover p-0 h-100" style="overflow: hidden;">
            <div class="d-flex align-items-center justify-content-between p-3" style="background: #101b3d;">
                <h2 class="h6 tm-serif fw-bold mb-0 text-white">Employee workload</h2>
                <div class="d-flex align-items-center gap-2" style="font-size: .72rem;">
                    <span class="badge rounded-pill fw-normal" style="border: 1px solid #3b82f6; color: #fff; background: #3b82f6;">Open</span>
                    <span class="badge rounded-pill fw-normal" style="background: #4a9b3e; color: #fff;">Done</span>
                </div>
            </div>
            <div class="p-3">
                @foreach ($employeeWorkload as $employee)
                    @php $employeeTotal = $employee['open'] + $employee['done']; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size: .74rem;">
                            <span class="fw-semibold" style="font-size: .8rem;">{{ $employee['name'] }}</span>
                            <span><span style="color: #0d6efd;">{{ $employee['open'] }} open</span> <span class="tm-muted">&middot;</span> <span style="color: #4a9b3e;">{{ $employee['done'] }} done</span></span>
                        </div>
                        <div class="tm-progress" style="background: #dbe9ff; overflow: hidden;">
                            <span style="display:block;height:100%;float:left;border-radius:0;width: {{ (int) round($employee['done'] / $employeeTotal * 100) }}%; background:#4a9b3e;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="tm-card tm-card-hover p-0" style="overflow: hidden;">
    <div class="d-flex align-items-center justify-content-between p-3" style="background: #101b3d;">
        <h2 class="h6 tm-serif fw-bold mb-0 text-white">Tickets by service</h2>
        <div class="d-flex align-items-center gap-2" style="font-size: .72rem;">
            <span class="badge rounded-pill fw-normal" style="border: 1px solid #3b82f6; color: #fff; background: #3b82f6;">Open</span>
            <span class="badge rounded-pill fw-normal" style="background: #4a9b3e; color: #fff;">Completed</span>
        </div>
    </div>
    <div class="p-3">
        <div class="row g-4">
            @foreach (collect($ticketsByService)->chunk((int) ceil(count($ticketsByService) / 2)) as $column)
                <div class="col-12 col-md-6">
                    @foreach ($column as $row)
                        @php $rowTotal = $row['completed'] + $row['open']; @endphp
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width: 170px; font-size: .78rem;">{{ $row['name'] }}</div>
                            <div class="tm-progress flex-grow-1" style="background: #e5e7ec; overflow: hidden;">
                                <span title="{{ $row['completed'] }} completed tickets" style="display:block;height:100%;float:left;border-radius:0;width: {{ (int) round($row['completed'] / $rowTotal * 100) }}%; background:#4a9b3e; cursor: default;"></span>
                                <span title="{{ $row['open'] }} pending tickets" style="display:block;height:100%;float:left;border-radius:0;width: {{ (int) round($row['open'] / $rowTotal * 100) }}%; background:#0d6efd; cursor: default;"></span>
                            </div>
                            <div class="fw-semibold text-end" style="width: 30px; font-size: .78rem;">{{ $rowTotal }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        document.querySelectorAll('.js-count-up').forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-count')) || 0;
            var prefix = el.getAttribute('data-prefix') || '';
            var duration = 1200;
            var start = null;

            function step(timestamp) {
                if (start === null) {
                    start = timestamp;
                }

                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.round(eased * target);

                el.textContent = prefix + current.toLocaleString('en-IN');

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = prefix + target.toLocaleString('en-IN');
                }
            }

            requestAnimationFrame(step);
        });
    })();

    (function () {
        var months = @json($months);

        function areaGradient(topColor, bottomColor) {
            return function (context) {
                var chart = context.chart;
                var chartArea = chart.chartArea;

                if (!chartArea) {
                    return null;
                }

                var gradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, topColor);
                gradient.addColorStop(1, bottomColor);

                return gradient;
            };
        }

        new Chart(document.getElementById('chartEnquiriesVsCompleted'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'New enquiries',
                        data: @json($newEnquiries),
                        borderColor: '#2f5fbe',
                        backgroundColor: areaGradient('rgba(47, 95, 190, .35)', 'rgba(47, 95, 190, 0)'),
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2f5fbe',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Completed',
                        data: @json($completedTickets),
                        borderColor: '#4a9b3e',
                        backgroundColor: areaGradient('rgba(74, 155, 62, .32)', 'rgba(74, 155, 62, 0)'),
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4a9b3e',
                        pointBorderWidth: 2,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        backgroundColor: '#101b3d',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        padding: 10,
                        boxPadding: 4,
                        displayColors: true,
                    },
                },
                scales: {
                    y: {beginAtZero: true, grid: {color: '#eef0f4'}},
                    x: {grid: {display: false}},
                },
            },
        });

        new Chart(document.getElementById('chartStatusBreakdown'), {
            type: 'doughnut',
            data: {
                labels: @json(array_column($statusBreakdown, 'label')),
                datasets: [{
                    data: @json(array_column($statusBreakdown, 'count')),
                    backgroundColor: @json(array_column($statusBreakdown, 'color')),
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {legend: {display: false}},
            },
            plugins: [{
                id: 'centerText',
                afterDraw: function (chart) {
                    var ctx = chart.ctx;
                    var width = chart.width;
                    var height = chart.height;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 20px "Helvetica Neue", Arial, sans-serif';
                    ctx.fillStyle = '#1f2430';
                    ctx.fillText('{{ $openTicketsTotal }}', width / 2, height / 2 - 8);
                    ctx.font = '11px "Helvetica Neue", Arial, sans-serif';
                    ctx.fillStyle = '#6b7280';
                    ctx.fillText('open tickets', width / 2, height / 2 + 12);
                    ctx.restore();
                },
            }],
        });

        new Chart(document.getElementById('chartFeesByMonth'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {label: 'Received', data: @json($feesReceived), backgroundColor: '#4a9b3e', stack: 'fees', categoryPercentage: 0.5, barPercentage: 0.9},
                    {label: 'Pending', data: @json($feesPending), backgroundColor: '#0d6efd', stack: 'fees', categoryPercentage: 0.5, barPercentage: 0.9},
                ],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        backgroundColor: '#101b3d',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        padding: 10,
                        boxPadding: 4,
                        displayColors: true,
                    },
                },
                scales: {
                    y: {stacked: true, beginAtZero: true, grid: {color: '#eef0f4'}},
                    x: {stacked: true, grid: {display: false}},
                },
            },
        });

        new Chart(document.getElementById('chartFeeCollection'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [81, 19],
                    backgroundColor: ['#4a9b3e', '#eef0f4'],
                    borderWidth: 0,
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {legend: {display: false}, tooltip: {enabled: false}},
            },
        });
    })();
</script>
@endpush
@endsection
