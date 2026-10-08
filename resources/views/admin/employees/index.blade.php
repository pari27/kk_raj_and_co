@extends('layouts.app')

@section('title', 'Employees — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $activeCount = $employees->where('is_active', true)->count();
    $inactiveCount = $employees->count() - $activeCount;

    $statCards = [
        ['label' => 'Total employees', 'count' => $employees->count(), 'caption' => 'in the master list', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Active', 'count' => $activeCount, 'caption' => 'currently working', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Inactive', 'count' => $inactiveCount, 'caption' => 'not assigned work', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
        ['label' => 'Active designations', 'count' => $designationsCount, 'caption' => 'roles in use', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
    ];
@endphp

<x-page-header title="Employees" subtitle="Team members who work on client tickets" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Employees']]">
    <x-slot:actions><a href="{{ route('admin.employees.create') }}" class="btn btn-tm-primary">+ Add Employee</a></x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach ($statCards as $card)
        <div class="col-6 col-xl-3">
            <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div class="position-relative">
                    <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ number_format($card['count']) }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
@endif

<div class="tm-card p-0">
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-lg-5">
                <input type="search" id="employeesSearch" class="form-control form-control-sm tm-field" placeholder="Search name, email, phone or designation" aria-label="Search employees">
            </div>
            <div class="col-12 col-lg-7 d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <div class="d-flex flex-wrap gap-1" role="group" aria-label="Filter employees by status">
                    <button type="button" class="tm-filter-pill active" data-filter="all">All {{ $employees->count() }}</button>
                    <button type="button" class="tm-filter-pill" data-filter="active">Active {{ $activeCount }}</button>
                    <button type="button" class="tm-filter-pill" data-filter="inactive">Inactive {{ $inactiveCount }}</button>
                </div>
                <span class="tm-muted small">{{ $designationsCount }} active designations</span>
            </div>
        </div>
    </div>

    @if ($employees->isEmpty())
        <x-empty-state title="No employees yet" description="Add your first employee to get started." />
    @else
        <div class="table-responsive">
            <table id="employeesTable" class="table tm-table align-middle mb-0 w-100">
                <thead><tr><th>Employee</th><th>Contact</th><th>Designation</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($employees as $employee)
                        @php
                            $nameParts = collect(explode(' ', trim($employee->name)))->filter();
                            $initials = strtoupper($nameParts->take(2)->map(fn ($part) => substr($part, 0, 1))->implode(''));
                        @endphp
                        <tr data-status="{{ $employee->is_active ? 'active' : 'inactive' }}">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($employee->profile?->photo_path)
                                        <img src="{{ asset('storage/' . $employee->profile->photo_path) }}" alt="" class="tm-service-avatar rounded-circle object-fit-cover">
                                    @else
                                        <span class="tm-service-avatar rounded-circle bg-primary-subtle text-primary">{{ $initials }}</span>
                                    @endif
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="fw-semibold text-decoration-none text-body" style="font-size: .8rem;">{{ $employee->name }}</a>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: .8rem;">{{ $employee->email }}</div>
                                <div class="tm-muted small">{{ $employee->profile?->mobile ? '+91 ' . $employee->profile->mobile : 'Phone not set' }}</div>
                            </td>
                            <td style="font-size: .8rem;">{{ $employee->profile?->designation?->name ?? '—' }}</td>
                            <td><span class="badge rounded-pill {{ $employee->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="tm-icon-btn-sm text-decoration-none" aria-label="View {{ $employee->name }}" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="tm-icon-btn-sm text-decoration-none" aria-label="Edit {{ $employee->name }}" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14v-7"></path><path d="m18.5 2.5 3 3L12 15l-4 1 1-4Z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('employeesTable');
        if (!table) {
            return;
        }

        const queryInput = document.getElementById('employeesSearch');
        const buttons = Array.from(document.querySelectorAll('[data-filter]'));
        const rows = Array.from(table.querySelectorAll('tbody tr[data-status]'));
        let activeFilter = 'all';

        const applyFilter = () => {
            const query = queryInput.value.trim().toLocaleLowerCase();
            rows.forEach((row) => {
                const matchesStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
                row.hidden = !matchesStatus || !row.textContent.toLocaleLowerCase().includes(query);
            });
        };

        queryInput.addEventListener('input', applyFilter);
        buttons.forEach((button) => button.addEventListener('click', () => {
            activeFilter = button.dataset.filter;
            buttons.forEach((item) => item.classList.toggle('active', item === button));
            applyFilter();
        }));
    });
</script>
@endsection