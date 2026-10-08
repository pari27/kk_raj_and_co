@extends('layouts.app')

@section('title', 'Services — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $activeCount = $services->where('is_active', true)->count();
    $inactiveCount = $services->count() - $activeCount;
    $avatarClasses = [
        'bg-primary-subtle text-primary',
        'bg-success-subtle text-success',
        'bg-info-subtle text-info',
        'bg-danger-subtle text-danger',
        'bg-warning-subtle text-warning',
    ];

    $statCards = [
        ['label' => 'Total services', 'count' => $services->count(), 'prefix' => '', 'caption' => 'in the master list', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Active', 'count' => $activeCount, 'prefix' => '', 'caption' => 'available to enquiries', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Inactive', 'count' => $inactiveCount, 'prefix' => '', 'caption' => 'hidden from new enquiries', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
        ['label' => 'Avg. price', 'count' => $services->count() ? round($services->avg('default_price')) : 0, 'prefix' => '₹', 'caption' => 'across all services', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
    ];
@endphp

<x-page-header
    title="Master Services"
    subtitle="Master list of billable services offered to clients"
    :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Services']]"
>
    <x-slot:actions>
        <a href="{{ route('admin.services.create') }}" class="btn btn-tm-primary">+ Add Master Service</a>
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
                    <div class="h3 tm-serif fw-bold mb-1 text-white">{{ $card['prefix'] }}{{ number_format($card['count']) }}</div>
                    <div class="small" style="color: rgba(255,255,255,.75);">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<div class="tm-card p-0">
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-xl-4">
                <input
                    type="search"
                    id="servicesSearch"
                    class="form-control form-control-sm tm-field"
                    placeholder="Search services"
                    aria-label="Search services"
                >
            </div>

            <div class="col-12 col-xl-8 d-flex flex-wrap align-items-center justify-content-xl-end gap-2">
               

                <label class="visually-hidden" for="servicesSort">Sort services</label>
                <select id="servicesSort" class="form-select form-select-sm tm-field w-auto">
                    <option value="name-asc">Sort: Name A-Z</option>
                    <option value="name-desc">Sort: Name Z-A</option>
                    <option value="price-asc">Sort: Price low-high</option>
                    <option value="price-desc">Sort: Price high-low</option>
                </select>
            </div>
        </div>
    </div>

    @if ($services->isEmpty())
        <x-empty-state title="No services yet" description="Add your first billable service to get started." />
    @else
        <div class="table-responsive">
            <table id="servicesTable" class="table tm-table align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Fee</th>
                        <th>Requirements</th>
                        <th>Tickets</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $index => $service)
                        @php
                            $words = collect(explode(' ', trim($service->name)))->filter();
                            $initials = strtoupper($words->take(2)->map(fn ($word) => substr($word, 0, 1))->implode(''));
                            $gstPercent = rtrim(rtrim(number_format((float) $service->gst_percent, 2, '.', ''), '0'), '.');
                        @endphp
                        <tr
                            data-status="{{ $service->is_active ? 'active' : 'inactive' }}"
                            data-name="{{ $service->name }}"
                            data-price="{{ (float) $service->default_price }}"
                            data-index="{{ $index }}"
                        >
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="tm-service-avatar {{ $avatarClasses[$index % count($avatarClasses)] }}">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <a
                                            href="{{ route('admin.services.show', $service) }}"
                                            class="fw-semibold text-decoration-none {{ $service->is_active ? 'text-body' : 'tm-muted' }}"
                                            style="font-size: .8rem;"
                                        >{{ $service->name }}</a>
                                        @if ($service->description)
                                            <div class="tm-muted small">{{ $service->description }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">₹{{ number_format((float) $service->default_price, 0) }}</div>
                                <div class="tm-muted small">₹{{ number_format($service->totalFee(), 0) }} with {{ $gstPercent }}% GST</div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-1 small">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path>
                                        <path d="M14 2v6h6"></path>
                                    </svg>
                                    {{ $service->documents_count }} {{ $service->documents_count === 1 ? 'document' : 'documents' }}
                                </span>
                            </td>
                            <td><span class="tm-muted small">Coming soon</span></td>
                            <td>
                                <span class="badge rounded-pill {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.services.show', $service) }}" class="tm-icon-btn-sm text-decoration-none" aria-label="View {{ $service->name }}" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.services.edit', $service) }}" class="tm-icon-btn-sm text-decoration-none" aria-label="Edit {{ $service->name }}" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4Z"></path>
                                        </svg>
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
        const table = document.getElementById('servicesTable');

        if (!table) {
            return;
        }

        const body = table.querySelector('tbody');
        const searchInput = document.getElementById('servicesSearch');
        const sortSelect = document.getElementById('servicesSort');
        const filterButtons = Array.from(document.querySelectorAll('[data-filter]'));
        const rows = Array.from(body.querySelectorAll('tr[data-status]'));
        let activeFilter = 'all';

        const sortRows = () => {
            const [key, direction] = sortSelect.value.split('-');
            const multiplier = direction === 'desc' ? -1 : 1;

            rows.sort((first, second) => {
                let comparison = 0;

                if (key === 'price') {
                    comparison = Number(first.dataset.price) - Number(second.dataset.price);
                } else {
                    comparison = first.dataset.name.localeCompare(second.dataset.name, undefined, { sensitivity: 'base' });
                }

                return (comparison * multiplier) || (Number(first.dataset.index) - Number(second.dataset.index));
            });

            rows.forEach((row) => body.appendChild(row));
        };

        const filterRows = () => {
            const query = searchInput.value.trim().toLocaleLowerCase();

            rows.forEach((row) => {
                const matchesStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
                const matchesSearch = row.textContent.toLocaleLowerCase().includes(query);

                row.hidden = !matchesStatus || !matchesSearch;
            });
        };

        searchInput.addEventListener('input', filterRows);
        sortSelect.addEventListener('change', sortRows);

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.filter;
                filterButtons.forEach((filterButton) => filterButton.classList.toggle('active', filterButton === button));
                filterRows();
            });
        });
    });
</script>
@endsection