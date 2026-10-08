@extends('layouts.app')

@section('title', 'Master Employee Designations — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    #designations-page .desig-action-btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
    }
    #designations-page .tm-filter-pill {
        font-size: .8rem;
    }
    #designations-page .desig-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    #designationsTable.tm-table tbody td {
        font-size: .8rem;
    }
    #designationsTable.tm-table thead th:first-child,
    #designationsTable.tm-table thead th:last-child {
        border-radius: 0;
    }
    .desig-modal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    .desig-modal-checklist li {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        padding: .3rem 0;
        font-size: .85rem;
    }
    .desig-modal-employees-box {
        background: #f5f6f8;
        border-radius: .6rem;
        padding: .6rem .75rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        font-size: .82rem;
    }
    #editDesignationModal .modal-header {
        background: #101b3d;
        color: #fff;
    }
    #editDesignationModal .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
</style>
@endpush

@section('content')
@php
    $totalCount = $designations->count();
    $activeCount = $designations->where('is_active', true)->count();
    $inactiveCount = $totalCount - $activeCount;
    $employeesAssigned = $designations->sum(fn ($designation) => $designation->employees->count());

    $statCards = [
        ['label' => 'Designations', 'count' => $totalCount, 'caption' => 'in the master list', 'gradient' => 'linear-gradient(135deg, #060e24, #0a4fc4)'],
        ['label' => 'Active', 'count' => $activeCount, 'caption' => 'available for staff', 'gradient' => 'linear-gradient(135deg, #0a2e14, #1f6b30)'],
        ['label' => 'Inactive', 'count' => $inactiveCount, 'caption' => 'hidden from new staff', 'gradient' => 'linear-gradient(135deg, #300a0a, #7f1616)'],
        ['label' => 'Employees', 'count' => $employeesAssigned, 'caption' => 'assigned a designation', 'gradient' => 'linear-gradient(135deg, #380c33, #6e1d58)'],
    ];

    $iconClasses = [
        'bg-primary-subtle text-primary',
        'bg-success-subtle text-success',
        'bg-info-subtle text-info',
        'bg-warning-subtle text-warning',
        'bg-secondary-subtle text-secondary',
    ];

    $miniAvatarClasses = [
        'bg-primary-subtle text-primary',
        'bg-success-subtle text-success',
        'bg-info-subtle text-info',
        'bg-danger-subtle text-danger',
        'bg-warning-subtle text-warning',
    ];

    $joinNames = function ($names) {
        $names = $names->values();
        $count = $names->count();

        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $names[0];
        }
        if ($count === 2) {
            return $names[0].' and '.$names[1];
        }

        $remaining = $count - 2;

        return $names->slice(0, 2)->implode(', ').' and '.$remaining.' other'.($remaining === 1 ? '' : 's');
    };
@endphp

<div id="designations-page">
    <x-page-header title="Employee designations" subtitle="Job titles used when adding staff" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Designations']]" />

    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ($statCards as $card)
            <div class="col-6 col-xl-3">
                <div class="tm-stat-card p-3 h-100 text-white position-relative" style="background: {{ $card['gradient'] }}; border: 0; border-radius: .6rem; overflow: hidden;">
                    <span class="position-absolute rounded-circle" style="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                    <span class="position-absolute rounded-circle" style="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                    <div class="position-relative">
                        <div class="small mb-2" style="color: rgba(255,255,255,.75);">{{ $card['label'] }}</div>
                        <div class="h3 tm-serif fw-bold mb-1 text-white">{{ number_format($card['count']) }}</div>
                        <div style="color: rgba(255,255,255,.75); font-size: .72rem;">{{ $card['caption'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="tm-card p-0">
                <div class="p-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-lg-6"><input type="search" id="designationsSearch" class="form-control form-control-sm tm-field" placeholder="Search designations" aria-label="Search designations"></div>
                        <div class="col-12 col-lg-6 d-flex flex-wrap justify-content-lg-end gap-1" role="group" aria-label="Filter designations by status">
                            <button type="button" class="tm-filter-pill active" data-filter="all">All {{ $totalCount }}</button>
                            <button type="button" class="tm-filter-pill" data-filter="active">Active {{ $activeCount }}</button>
                            <button type="button" class="tm-filter-pill" data-filter="inactive">Inactive {{ $inactiveCount }}</button>
                        </div>
                    </div>
                </div>

                @if ($designations->isEmpty())
                    <x-empty-state title="No designations yet" description="Add a designation for your staff members." />
                @else
                    <div class="table-responsive">
                        <table id="designationsTable" class="table tm-table align-middle mb-0 w-100">
                            <thead><tr><th>Designation</th><th>Employees</th><th>Created</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($designations as $index => $designation)
                                    @php
                                        $employeesJson = $designation->employees->take(3)->values()->map(fn ($employee, $i) => [
                                            'initials' => strtoupper(collect(explode(' ', trim($employee->name)))->filter()->take(2)->map(fn ($part) => substr($part, 0, 1))->implode('')),
                                            'color' => $miniAvatarClasses[$i % count($miniAvatarClasses)],
                                        ]);
                                        $employeeNamesJoined = $joinNames($designation->employees->pluck('name'));
                                    @endphp
                                    <tr data-status="{{ $designation->is_active ? 'active' : 'inactive' }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="tm-designation-icon {{ $iconClasses[$index % count($iconClasses)] }}" aria-hidden="true"><i class="bi bi-award"></i></span>
                                                <span class="fw-semibold">{{ $designation->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($designation->employees->isNotEmpty())
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="d-flex">
                                                        @foreach ($designation->employees->take(3) as $i => $employee)
                                                            @php
                                                                $empInitials = collect(explode(' ', trim($employee->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
                                                            @endphp
                                                            <span class="tm-mini-avatar {{ $miniAvatarClasses[$i % count($miniAvatarClasses)] }}" title="{{ $employee->name }}">{{ $empInitials }}</span>
                                                        @endforeach
                                                    </div>
                                                    <span>{{ $designation->employees->count() }} {{ $designation->employees->count() === 1 ? 'employee' : 'employees' }}</span>
                                                </div>
                                            @else
                                                <span class="tm-muted small">No employees</span>
                                            @endif
                                        </td>
                                        <td>{{ $designation->created_at->format('d M Y') }}</td>
                                        <td>
                                            <span class="desig-status-dot" style="background: {{ $designation->is_active ? '#1f6b30' : '#9aa1b0' }};"></span>
                                            <span class="{{ $designation->is_active ? 'text-success' : 'tm-muted' }} fw-semibold">{{ $designation->is_active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex justify-content-end gap-1">
                                                <button
                                                    type="button"
                                                    class="desig-action-btn js-edit-designation"
                                                    style="background: #e5f5e0;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editDesignationModal"
                                                    data-action="{{ route('admin.designations.update', $designation) }}"
                                                    data-name="{{ $designation->name }}"
                                                    data-active="{{ $designation->is_active ? '1' : '0' }}"
                                                    data-employee-count="{{ $designation->employees->count() }}"
                                                    data-employee-names="{{ $employeeNamesJoined }}"
                                                    data-employees='{{ $employeesJson->toJson() }}'
                                                    aria-label="Edit {{ $designation->name }}"
                                                    title="Edit"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1f6b30" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14v-7"></path><path d="m18.5 2.5 3 3L12 15l-4 1 1-4Z"></path></svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="desig-action-btn js-toggle-designation"
                                                    style="background: {{ $designation->is_active ? '#fbe5ea' : '#e5f5e0' }}; color: {{ $designation->is_active ? '#c0392b' : '#1f6b30' }};"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#toggleDesignationModal"
                                                    data-action="{{ route('admin.designations.toggle-active', $designation) }}"
                                                    data-name="{{ $designation->name }}"
                                                    data-active="{{ $designation->is_active ? '1' : '0' }}"
                                                    data-employee-count="{{ $designation->employees->count() }}"
                                                    data-employee-names="{{ $employeeNamesJoined }}"
                                                    data-employees='{{ $employeesJson->toJson() }}'
                                                    aria-label="{{ $designation->is_active ? 'Deactivate' : 'Activate' }} {{ $designation->name }}"
                                                    title="{{ $designation->is_active ? 'Deactivate' : 'Activate' }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 tm-muted" style="font-size: .72rem;">Showing 1–{{ $totalCount }} of {{ $totalCount }} designations</div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card p-0 overflow-hidden">
                <div class="tm-section-title" style="background: #1f6b30;">Add designation</div>
                <form method="POST" action="{{ route('admin.designations.store') }}" class="p-3">
                    @csrf
                    <div class="mb-3">
                        <label class="tm-field-label" for="newDesignationName">Designation name  <span class="text-danger">*</span></label>
                        <input type="text" id="newDesignationName" name="name" value="{{ old('name') }}" maxlength="60" class="form-control tm-field @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" id="newDesignationActive" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="newDesignationActive" style="font-size: .8rem;">Active</label>
                    </div>
                    <button type="submit" class="btn btn-tm-primary w-100">Save designation</button>
                    <div class="alert alert-light border mt-3 mb-0" style="font-size: .72rem;">Designations can't be deleted. Make one inactive to hide it from new staff; existing staff keep it.</div>
                </form>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="editDesignationModal" tabindex="-1" aria-labelledby="editDesignationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editDesignationForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(255,255,255,.15);"><i class="bi bi-award text-white"></i></span>
                        <div>
                            <h2 class="modal-title fs-6 fw-bold mb-0" id="editDesignationTitle">Edit designation</h2>
                            <div id="editModalEmployeeSubtitle" style="color: rgba(255,255,255,.75); font-size: .8rem;">Not used by any employees</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="tm-field-label" for="editDesignationName">Designation name <span class="text-danger">*</span></label>
                    <input type="text" id="editDesignationName" name="name" maxlength="60" class="form-control tm-field mb-1" required>
                    <div class="tm-muted mb-3" style="font-size: .72rem;">Must be unique. <span id="editModalCharCount">0</span> / 60 characters</div>

                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="fw-semibold" style="font-size: .85rem;">Active</div>
                            <div class="tm-muted" style="font-size: .75rem;">Shows in the designation list when adding staff</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" id="editDesignationActive" name="is_active" value="1" class="form-check-input" role="switch">
                        </div>
                    </div>

                    <div class="desig-modal-employees-box mb-3 d-none" id="editModalEmployeesBox" style="font-size: .8rem;">
                        <div class="d-flex" id="editModalMiniAvatars"></div>
                        <span><span id="editModalEmployeeNames"></span> have this designation</span>
                    </div>

                    <div class="alert alert-warning mb-0 d-none" id="editModalWarningBox" style="font-size: .8rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Renaming updates it for all employees who have it. Making it inactive keeps it on their records but hides it for new staff.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background: #1f6b30;">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="toggleDesignationModal" tabindex="-1" aria-labelledby="toggleDesignationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="toggleDesignationForm">
                @csrf
                @method('PATCH')
                <div class="modal-body text-center pt-4 px-4 pb-2 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

                    <div class="desig-modal-icon mb-3" id="toggleModalIconWrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="toggleModalIconSvg"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                    </div>

                    <h2 class="fs-5 fw-bold mb-2" id="toggleDesignationTitle">Activate designation?</h2>
                    <p class="mb-3"><strong id="toggleModalName"></strong> <span id="toggleModalStateText">will be active.</span></p>

                    <div class="desig-modal-employees-box mb-3 d-none text-start" id="toggleModalEmployeesBox">
                        <div class="d-flex" id="toggleModalMiniAvatars"></div>
                        <span><strong id="toggleModalEmployeeCount"></strong> currently have this designation</span>
                    </div>

                    <ul class="list-unstyled text-start mb-0 desig-modal-checklist" id="toggleModalChecklist"></ul>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white px-4" id="toggleModalSubmitBtn">Yes, activate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const search = document.getElementById('designationsSearch');
        const buttons = Array.from(document.querySelectorAll('[data-filter]'));
        const rows = Array.from(document.querySelectorAll('tbody tr[data-status]'));
        let activeFilter = 'all';

        const filterRows = () => {
            const query = search.value.trim().toLocaleLowerCase();
            rows.forEach((row) => {
                const matchesStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
                row.hidden = !matchesStatus || !row.textContent.toLocaleLowerCase().includes(query);
            });
        };

        search.addEventListener('input', filterRows);
        buttons.forEach((button) => button.addEventListener('click', () => {
            activeFilter = button.dataset.filter;
            buttons.forEach((item) => item.classList.toggle('active', item === button));
            filterRows();
        }));

        const checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-1"><polyline points="20 6 9 17 4 12"></polyline></svg>';

        const buildMiniAvatars = (container, employees) => {
            container.innerHTML = '';
            employees.forEach((employee) => {
                const span = document.createElement('span');
                span.className = 'tm-mini-avatar ' + employee.color;
                span.textContent = employee.initials;
                container.appendChild(span);
            });
        };

        // Edit modal
        document.querySelectorAll('.js-edit-designation').forEach((button) => button.addEventListener('click', () => {
            const form = document.getElementById('editDesignationForm');
            const nameInput = document.getElementById('editDesignationName');
            const employeeCount = parseInt(button.dataset.employeeCount, 10) || 0;
            const employees = JSON.parse(button.dataset.employees || '[]');

            form.action = button.dataset.action;
            nameInput.value = button.dataset.name;
            document.getElementById('editDesignationActive').checked = button.dataset.active === '1';
            document.getElementById('editModalCharCount').textContent = button.dataset.name.length;

            document.getElementById('editModalEmployeeSubtitle').textContent = employeeCount === 0
                ? 'Not used by any employees'
                : 'Used by ' + employeeCount + ' ' + (employeeCount === 1 ? 'employee' : 'employees');

            const employeesBox = document.getElementById('editModalEmployeesBox');
            const warningBox = document.getElementById('editModalWarningBox');

            if (employeeCount > 0) {
                buildMiniAvatars(document.getElementById('editModalMiniAvatars'), employees);
                document.getElementById('editModalEmployeeNames').textContent = button.dataset.employeeNames;
                employeesBox.classList.remove('d-none');
                warningBox.classList.remove('d-none');
            } else {
                employeesBox.classList.add('d-none');
                warningBox.classList.add('d-none');
            }
        }));

        document.getElementById('editDesignationName').addEventListener('input', (event) => {
            document.getElementById('editModalCharCount').textContent = event.target.value.length;
        });

        // Toggle active/inactive modal
        document.querySelectorAll('.js-toggle-designation').forEach((button) => button.addEventListener('click', () => {
            const isActive = button.dataset.active === '1';
            const willActivate = !isActive;
            const employeeCount = parseInt(button.dataset.employeeCount, 10) || 0;
            const employees = JSON.parse(button.dataset.employees || '[]');
            const name = button.dataset.name;

            document.getElementById('toggleDesignationForm').action = button.dataset.action;
            document.getElementById('toggleModalName').textContent = name;
            document.getElementById('toggleModalName').style.color = willActivate ? '#1f6b30' : '#981f27';
            document.getElementById('toggleDesignationTitle').textContent = willActivate ? 'Activate designation?' : 'Deactivate designation?';
            document.getElementById('toggleModalStateText').textContent = willActivate ? 'will be active.' : 'will be inactive.';

            const iconWrap = document.getElementById('toggleModalIconWrap');
            const iconSvg = document.getElementById('toggleModalIconSvg');
            iconWrap.style.background = willActivate ? '#e5f5e0' : '#fbe5ea';
            iconSvg.style.color = willActivate ? '#1f6b30' : '#981f27';

            const submitBtn = document.getElementById('toggleModalSubmitBtn');
            submitBtn.textContent = willActivate ? 'Yes, activate' : 'Yes, deactivate';
            submitBtn.style.background = willActivate ? '#1f6b30' : '#981f27';

            const employeesBox = document.getElementById('toggleModalEmployeesBox');
            if (!willActivate && employeeCount > 0) {
                buildMiniAvatars(document.getElementById('toggleModalMiniAvatars'), employees);
                document.getElementById('toggleModalEmployeeCount').textContent = employeeCount + ' ' + (employeeCount === 1 ? 'employee' : 'employees');
                employeesBox.classList.remove('d-none');
            } else {
                employeesBox.classList.add('d-none');
            }

            const checklist = document.getElementById('toggleModalChecklist');
            let items = [];
            if (willActivate) {
                items = [
                    'It will appear again when adding or editing staff',
                    employeeCount === 0 ? 'No employees have it right now' : employeeCount + ' ' + (employeeCount === 1 ? 'employee has' : 'employees currently have') + ' this designation',
                    'You can deactivate it again at any time',
                ];
            } else {
                items = [
                    employeeCount > 0 ? (button.dataset.employeeNames + ' keep' + (employeeCount === 1 ? 's' : '') + ' this designation') : 'No employees currently have this designation',
                    "It won't appear when adding new staff",
                    'You can activate it again at any time',
                ];
            }

            checklist.innerHTML = items.map((text) => '<li>' + checkIcon + '<span>' + text + '</span></li>').join('');
        }));
    });
</script>
@endsection
