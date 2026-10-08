@extends('layouts.app')

@section('title', $employee->name . ' — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    #employee-show-page .emp-stat-card {
        min-height: 110px;
        color: #fff;
        border: 0;
        border-radius: .85rem;
        box-shadow: 0 5px 16px rgba(16, 27, 61, .15);
    }
    #employee-show-page .emp-section-title {
        background: #101b3d;
        color: #fff;
        padding: .85rem 1.15rem;
        border-radius: .8rem .8rem 0 0;
    }
    #employee-show-page .emp-section-title h2 {
        font-size: 1rem;
    }
    #employee-show-page .emp-side-heading {
        color: #fff;
        padding: .8rem 1.15rem;
        border-radius: .8rem .8rem 0 0;
    }
    #employee-show-page .emp-info-label {
        color: #6b7280;
    }
    #employee-show-page .emp-data-row + .emp-data-row {
        border-top: 1px solid #edf0f3;
    }
    #employee-show-page .emp-data-row,
    #employee-show-page .emp-data-row * {
        font-size: .8rem;
    }
    #employee-show-page .emp-status-row + .emp-status-row {
        border-top: 1px solid #edf0f3;
    }
    #employee-show-page .emp-status-bar-track {
        background: #eef0f4;
        border-radius: 1rem;
        height: 6px;
        overflow: hidden;
    }
    #employee-show-page .emp-status-bar-fill {
        height: 100%;
        border-radius: 1rem;
    }
    #assignedTicketsTable.tm-table tbody td {
        font-size: .8rem;
    }
    #assignedTicketsTable.tm-table thead th {
        background: #f5f6f8;
        color: #6b7280;
    }
    #assignedTicketsTable.tm-table thead th:first-child,
    #assignedTicketsTable.tm-table thead th:last-child {
        border-radius: 0;
    }
</style>
@endpush

@section('content')
@php
    $nameParts = collect(explode(' ', trim($employee->name)))->filter();
    $initials = strtoupper($nameParts->take(2)->map(fn ($part) => substr($part, 0, 1))->implode(''));

    $variantColors = [
        'secondary' => '#6b7280',
        'dark' => '#343a40',
        'info' => '#17857a',
        'primary' => '#6e1d58',
        'warning' => '#b8860b',
        'danger' => '#981f27',
        'success' => '#1f6b30',
    ];

    $maxStatusCount = $openByStatus->max() ?: 1;

    $dotColor = function (string $action): string {
        return match ($action) {
            'Created', 'Activated' => '#1f6b30',
            'Deactivated' => '#7f1616',
            default => '#0a4fc4',
        };
    };
@endphp

<div id="employee-show-page">
    <x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Employees', 'url' => route('admin.employees.index')], ['label' => $employee->name]]" />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-3 tm-divider-gold">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #101b3d; color: #fff; font-weight: 700; border: 2px solid {{ $employee->is_active ? '#1f6b30' : '#9aa1b0' }};">
                @if ($employee->profile?->photo_path)
                    <img src="{{ asset('storage/' . $employee->profile->photo_path) }}" alt="" class="rounded-circle object-fit-cover" style="width: 100%; height: 100%;">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="tm-serif fw-bold mb-0" style="font-size: 1.15rem;">{{ $employee->name }}</h1>
                    <span class="badge rounded-pill {{ $employee->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                    @if ($employee->profile?->designation)
                        <span class="badge rounded-pill text-bg-light border">{{ $employee->profile->designation->name }}</span>
                    @endif
                </div>
                <div class="tm-muted" style="font-size: .8rem;">
                    Added {{ $employee->created_at->format('d M Y') }} by {{ $addedBy?->user?->name ?? 'System' }}
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-tm-primary">Edit Master Employee</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="tm-card emp-stat-card p-3" style="background: linear-gradient(135deg, #102b68, #2865d5);">
                <div class="small mb-2 opacity-75">Open tickets</div>
                <div class="h3 fw-bold mb-1">{{ number_format($openTicketsCount) }}</div>
                <div class="small opacity-75">assigned now</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card emp-stat-card p-3" style="background: linear-gradient(135deg, #0a2e14, #1f6b30);">
                <div class="small mb-2 opacity-75">Completed</div>
                <div class="h3 fw-bold mb-1">{{ number_format($completedTicketsCount) }}</div>
                <div class="small opacity-75">this FY</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card emp-stat-card p-3" style="background: linear-gradient(135deg, #380c33, #6e1d58);">
                <div class="small mb-2 opacity-75">Average time</div>
                <div class="h3 fw-bold mb-1">{{ $avgTurnaroundDays !== null ? $avgTurnaroundDays . ' days' : '—' }}</div>
                <div class="small opacity-75">enquiry to completion</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="tm-card emp-stat-card p-3" style="background: linear-gradient(135deg, #5c3d08, #8a5d0d);">
                <div class="small mb-2 opacity-75">Waiting on clients</div>
                <div class="h3 fw-bold mb-1">{{ number_format($waitingOnClientsCount) }}</div>
                <div class="small opacity-75">documents pending</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="emp-section-title"><h2 class="mb-0 fw-bold">Profile</h2></div>
                <div class="p-3 px-lg-4">
                    <div class="row g-0">
                        <div class="col-12 col-md-6 pe-md-4">
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Full name</span><strong class="text-end">{{ $employee->name }}</strong>
                            </div>
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Mobile</span><strong class="text-end">{{ $employee->profile?->mobile ? '+91 ' . $employee->profile->mobile : '—' }}</strong>
                            </div>
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Designation</span><strong class="text-end">{{ $employee->profile?->designation?->name ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 ps-md-4">
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Gender</span><strong class="text-end">{{ $employee->profile?->gender ?? '—' }}</strong>
                            </div>
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Email</span><strong class="text-end text-break">{{ $employee->email }}</strong>
                            </div>
                            <div class="emp-data-row d-flex justify-content-between gap-3 py-3">
                                <span class="emp-info-label">Status</span><strong class="text-end {{ $employee->is_active ? 'text-success' : 'text-secondary' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="emp-section-title d-flex align-items-center justify-content-between">
                    <h2 class="mb-0 fw-bold">Assigned tickets ({{ $assignedTicketsCount }})</h2>
                    <a href="{{ route('tickets.index') }}" class="small fw-semibold text-decoration-underline" style="color: #fff;">View all &rarr;</a>
                </div>
                @if ($recentTickets->isEmpty())
                    <div class="p-3"><x-empty-state title="No tickets yet" description="Tickets assigned to this employee will appear here." /></div>
                @else
                    <div class="table-responsive">
                        <table id="assignedTicketsTable" class="table tm-table align-middle mb-0">
                            <thead><tr><th>Ticket</th><th>Client</th><th>Status</th><th>Open for</th></tr></thead>
                            <tbody>
                                @foreach ($recentTickets as $ticket)
                                    <tr>
                                        <td><a href="{{ route('tickets.show', $ticket) }}" class="fw-semibold text-decoration-none">{{ $ticket->number }}</a><div class="tm-muted small">{{ $ticket->service?->name ?? '—' }}</div></td>
                                        <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                        <td><x-status-badge :status="$ticket->status->value" /></td>
                                        <td>{{ (int) $ticket->created_at->diffInDays($ticket->completed_at ?? now()) }} days</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="emp-section-title"><h2 class="mb-0 fw-bold">Recent activity</h2></div>
                <div class="p-4">
                    @forelse ($activity as $log)
                        <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="rounded-circle flex-shrink-0 mt-1" style="width: 8px; height: 8px; background: {{ $dotColor($log->action) }};"></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size: .8rem;">{{ $log->details ?: $log->action }}</div>
                                <div class="tm-muted" style="font-size: .7rem;">{{ $log->created_at->format('j M Y, g:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="tm-muted small mb-0">No activity recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="emp-side-heading" style="background: #1f6b30;"><h2 class="h6 fw-bold mb-0">Open tickets by status</h2></div>
                <div class="p-3">
                    @forelse ($openByStatus as $status => $count)
                        @php
                            $variant = \App\Enums\TicketStatus::tryFrom($status)?->badgeVariant() ?? 'secondary';
                            $color = $variantColors[$variant] ?? '#6b7280';
                        @endphp
                        <div class="emp-status-row py-2">
                            <div class="d-flex justify-content-between gap-3 mb-1" style="font-size: .8rem;">
                                <span>{{ $status }}</span><strong>{{ $count }}</strong>
                            </div>
                            <div class="emp-status-bar-track">
                                <div class="emp-status-bar-fill" style="width: {{ round($count / $maxStatusCount * 100) }}%; background: {{ $color }};"></div>
                            </div>
                        </div>
                    @empty
                        <p class="tm-muted small mb-0">No open tickets.</p>
                    @endforelse
                </div>
            </section>

            <section class="tm-card p-0 mb-3 overflow-hidden">
                <div class="emp-side-heading" style="background: #101b3d;"><h2 class="h6 fw-bold mb-0">Login and account</h2></div>
                <div class="p-3">
                    <div class="emp-data-row d-flex justify-content-between gap-3 py-2"><span class="emp-info-label">Login email</span><strong class="text-end text-break">{{ $employee->email }}</strong></div>
                    <div class="emp-data-row d-flex justify-content-between gap-3 py-2"><span class="emp-info-label">Password</span><strong>{{ $employee->hasSetPassword() ? 'Set' : 'Invitation pending' }}</strong></div>
                    <div class="emp-data-row d-flex justify-content-between gap-3 py-2"><span class="emp-info-label">Added by</span><strong>{{ $addedBy?->user?->name ?? '—' }}</strong></div>
                    @if (! $employee->hasSetPassword())
                        <form method="POST" action="{{ route('admin.employees.resend-invite', $employee) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Resend invitation</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="tm-card p-0 overflow-hidden">
                <div class="emp-side-heading" style="background: #0f6b5c;"><h2 class="h6 fw-bold mb-0">Tickets by service (FY)</h2></div>
                <div class="p-3">
                    @forelse ($ticketsByService as $serviceName => $count)
                        <div class="emp-data-row d-flex justify-content-between gap-3 py-2"><span class="emp-info-label">{{ $serviceName }}</span><strong>{{ $count }}</strong></div>
                    @empty
                        <p class="tm-muted small mb-0">No tickets this FY.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
