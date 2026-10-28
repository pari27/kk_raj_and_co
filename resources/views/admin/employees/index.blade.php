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

<x-page-header title="Employees" subtitle="Team members who work on client services" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Employees']]">
    <x-slot:actions><a href="{{ route('admin.employees.create') }}" class="btn btn-tm-primary">+ Add Employee</a></x-slot:actions>
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

@if (session('error'))
    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
@endif

<div class="tm-card p-0">
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-lg-5">
                <x-search-box id="employeesSearch" />
            </div>
            <div class="col-12 col-lg-7 d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <select id="employeesSort" class="form-select form-select-sm tm-field employees-sort" aria-label="Sort employees">
                    <option value="newest">Sort by: Newest first</option>
                    <option value="oldest">Sort by: Oldest first</option>
                    <option value="name-asc">Sort by: Name A–Z</option>
                    <option value="name-desc">Sort by: Name Z–A</option>
                </select>
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
                        <tr data-status="{{ $employee->is_active ? 'active' : 'inactive' }}" data-name="{{ strtolower($employee->name) }}" data-created="{{ $employee->created_at?->timestamp }}">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($employee->profile?->photo_path)
                                        <img src="{{ asset('storage/' . $employee->profile->photo_path) }}" alt="" class="tm-service-avatar rounded-circle object-fit-cover">
                                    @else
                                        <span class="tm-service-avatar rounded-circle bg-primary-subtle text-primary">{{ $initials }}</span>
                                    @endif
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="fw-semibold text-decoration-none text-body u-5a305f">{{ $employee->name }}</a>
                                </div>
                            </td>
                            <td>
                                <div class="u-5a305f">{{ $employee->email }}</div>
                                <div class="tm-muted small">{{ $employee->profile?->mobile ? '+91 ' . $employee->profile->mobile : 'Phone not set' }}</div>
                            </td>
                            <td class="u-5a305f">{{ $employee->profile?->designation?->name ?? '—' }}</td>
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

        <div class="d-flex align-items-center justify-content-between p-3 tm-muted u-cc37c5">
            <span id="employeesPaginationSummary"></span>
            <ul class="pagination pagination-sm mb-0" id="employeesPaginationPages"></ul>
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
        const PAGE_SIZE = 10;
        let currentPage = 1;
        const pageSummary = document.getElementById('employeesPaginationSummary');
        const pageList = document.getElementById('employeesPaginationPages');

        const renderPagination = (totalPages) => {
            pageList.innerHTML = '';

            const add = (label, page, disabled, active) => {
                const item = document.createElement('li');
                item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const link = document.createElement('a');
                link.className = 'page-link';
                link.href = '#';
                link.textContent = label;
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (!disabled && !active) {
                        currentPage = page;
                        applyFilter(true);
                    }
                });
                item.appendChild(link);
                pageList.appendChild(item);
            };

            add('‹', currentPage - 1, currentPage <= 1, false);
            for (let page = 1; page <= totalPages; page++) {
                add(String(page), page, false, page === currentPage);
            }
            add('›', currentPage + 1, currentPage >= totalPages, false);
        };

        function applyFilter(keepPage) {
            if (keepPage !== true) {
                currentPage = 1;
            }

            const query = queryInput.value.trim().toLocaleLowerCase();
            const matched = [];
            rows.forEach((row) => {
                const matchesStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
                row.hidden = true;
                if (matchesStatus && row.textContent.toLocaleLowerCase().includes(query)) {
                    matched.push(row);
                }
            });

            const totalPages = Math.max(1, Math.ceil(matched.length / PAGE_SIZE));
            currentPage = Math.min(currentPage, totalPages);
            const start = (currentPage - 1) * PAGE_SIZE;
            matched.slice(start, start + PAGE_SIZE).forEach((row) => { row.hidden = false; });

            pageSummary.textContent = matched.length
                ? 'Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, matched.length) + ' of ' + matched.length
                : 'No employees found';
            renderPagination(totalPages);
        }

        const sortSelect = document.getElementById('employeesSort');
        const tbody = table.querySelector('tbody');
        const sorters = {
            'newest': (a, b) => Number(b.dataset.created) - Number(a.dataset.created),
            'oldest': (a, b) => Number(a.dataset.created) - Number(b.dataset.created),
            'name-asc': (a, b) => a.dataset.name.localeCompare(b.dataset.name),
            'name-desc': (a, b) => b.dataset.name.localeCompare(a.dataset.name),
        };

        sortSelect.addEventListener('change', () => {
            rows.sort(sorters[sortSelect.value]);
            rows.forEach((row) => tbody.appendChild(row));
            applyFilter();
        });

        queryInput.addEventListener('input', () => applyFilter());
        buttons.forEach((button) => button.addEventListener('click', () => {
            activeFilter = button.dataset.filter;
            buttons.forEach((item) => item.classList.toggle('active', item === button));
            applyFilter();
        }));

        applyFilter();
    });
</script>
@endsection