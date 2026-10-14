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
        @if ($canManage)
            <a href="{{ route('admin.services.create') }}" class="btn btn-tm-primary">+ Add Master Service</a>
        @endif
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
                    <div class="small" style="color: rgba(255,255,255,.75); font-size: .72rem;">{{ $card['caption'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="tm-card p-0">
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-xl-4">
                <input
                    type="search"
                    id="servicesSearch"
                    class="form-control form-control-sm tm-field"
                    placeholder="Search by services name, description, fee or status"
                    aria-label="Search by services name, description, fee or status"
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
                        <th>Services</th>
                        <th>Status</th>
                        @if ($canManage)
                            <th class="text-end">Actions</th>
                        @endif
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
                                        @if ($canManage)
                                            <a
                                                href="{{ route('admin.services.show', $service) }}"
                                                class="fw-semibold text-decoration-none {{ $service->is_active ? 'text-body' : 'tm-muted' }}"
                                                style="font-size: .8rem;"
                                            >{{ $service->name }}</a>
                                        @else
                                            <span class="fw-semibold {{ $service->is_active ? '' : 'tm-muted' }}" style="font-size: .8rem;">{{ $service->name }}</span>
                                        @endif
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
                                @php
                                    $documentList = $service->documents->map(fn ($document) => [
                                        'name' => $document->name,
                                        'mandatory' => (bool) $document->is_mandatory,
                                        'instructions' => $document->instructions,
                                        'type' => $document->input_type,
                                        'formats' => $document->allowed_formats,
                                        'size' => $document->formattedMaxSize(),
                                    ])->values();
                                @endphp
                                <button
                                    type="button"
                                    class="btn btn-link p-0 text-decoration-none text-body d-inline-flex align-items-center gap-1 small js-show-documents"
                                    data-service="{{ $service->name }}"
                                    data-fee="₹{{ number_format((float) $service->default_price, 0) }}"
                                    data-edit-url="{{ $canManage ? route('admin.services.edit', $service) : '' }}"
                                    data-documents="{{ $documentList->toJson() }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#serviceDocumentsModal"
                                    title="View required documents"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path>
                                        <path d="M14 2v6h6"></path>
                                    </svg>
                                    {{ $service->documents_count }} {{ $service->documents_count === 1 ? 'document' : 'documents' }}
                                </button>
                            </td>
                            <td><span class="tm-muted small">Coming soon</span></td>
                            <td>
                                <span class="badge rounded-pill {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            @if ($canManage)
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
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between p-3 tm-muted" style="font-size: .75rem;">
            <span id="servicesPaginationSummary"></span>
            <ul class="pagination pagination-sm mb-0" id="servicesPaginationPages"></ul>
        </div>
    @endif
</div>

<div class="modal fade" id="serviceDocumentsModal" tabindex="-1" aria-labelledby="serviceDocumentsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 560px;">
        <div class="modal-content border-0" style="border-radius: 14px; overflow: hidden;">
            <div class="d-flex align-items-center gap-3 px-4 py-3 text-white" style="background: #101b3d;">
                <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.12);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path></svg>
                </span>
                <div class="flex-grow-1">
                    <h2 class="modal-title h6 fw-bold mb-0" id="serviceDocumentsTitle">Required documents</h2>
                    <div class="small" style="color: rgba(255,255,255,.75);" id="serviceDocumentsService"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="d-flex border-bottom text-start">
                <div class="flex-fill px-4 py-2 border-end"><div class="tm-muted small">Total</div><div class="fw-bold fs-5" id="serviceDocumentsTotal">0</div></div>
                <div class="flex-fill px-4 py-2 border-end"><div class="tm-muted small">Mandatory</div><div class="fw-bold fs-5 text-danger" id="serviceDocumentsMandatory">0</div></div>
                <div class="flex-fill px-4 py-2"><div class="tm-muted small">Optional</div><div class="fw-bold fs-5" id="serviceDocumentsOptional">0</div></div>
            </div>

            <div class="modal-body px-4 py-3" id="serviceDocumentsBody"></div>

            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="background: #fafbfc;">
                @if ($canManage)
                    <a href="#" id="serviceDocumentsEdit" class="btn btn-outline-success btn-sm">Edit documents</a>
                @else
                    <span></span>
                @endif
                <button type="button" class="btn btn-sm text-white px-3" style="background: #101b3d;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const documentsBody = document.getElementById('serviceDocumentsBody');
        const iconStyles = [
            ['#e8effd', '#1d4fb8'],
            ['#efe9fb', '#5b3fb3'],
            ['#e3f5f1', '#15806b'],
            ['#fdf0dc', '#b26a00'],
        ];

        const makeEl = (tag, className, text) => {
            const el = document.createElement(tag);
            if (className) { el.className = className; }
            if (text !== undefined) { el.textContent = text; }
            return el;
        };

        const renderGroup = (title, color, docs, startNumber) => {
            if (!docs.length) { return; }

            const heading = makeEl('div', 'd-inline-flex align-items-center gap-2 fw-semibold rounded-pill px-3 py-1 mb-2 mt-2 text-white');
            heading.style.fontSize = '.8rem';
            heading.style.background = color;
            heading.appendChild(makeEl('span', '', title));
            const count = makeEl('span', 'badge rounded-pill', String(docs.length));
            count.style.cssText = 'background: #fff; color: ' + color + ';';
            heading.appendChild(count);
            documentsBody.appendChild(heading);

            docs.forEach((doc, offset) => {
                const number = startNumber + offset;
                const [bg, fg] = iconStyles[(number - 1) % iconStyles.length];
                const row = makeEl('div', 'd-flex gap-3 py-3' + (offset < docs.length - 1 ? ' border-bottom' : ''));

                const iconWrap = makeEl('div', 'position-relative flex-shrink-0');
                const icon = makeEl('span', 'd-inline-flex align-items-center justify-content-center');
                icon.style.cssText = 'width: 40px; height: 40px; border-radius: 10px; background: ' + bg + '; color: ' + fg + ';';
                icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path></svg>';
                iconWrap.appendChild(icon);

                const info = makeEl('div', 'flex-grow-1');
                const name = makeEl('div', 'fw-bold', doc.name);
                name.style.fontSize = '.8rem';
                info.appendChild(name);

                if (doc.instructions) {
                    const note = makeEl('div', 'tm-muted', doc.instructions);
                    note.style.fontSize = '.8rem';
                    info.appendChild(note);
                }

                const meta = makeEl('div', 'd-flex flex-wrap align-items-center gap-1 mt-1');
                (doc.type === 'text' ? 'Text' : (doc.formats || '')).split(',').map((format) => format.trim()).filter(Boolean).forEach((format) => {
                    const chip = makeEl('span', 'badge fw-bold', format.toUpperCase());
                    chip.style.cssText = 'background: #eaf0fb; color: #1d3f8f; font-size: .62rem;';
                    meta.appendChild(chip);
                });
                if (doc.size) {
                    const size = makeEl('span', 'tm-muted', '· up to ' + doc.size);
                    size.style.fontSize = '.7rem';
                    meta.appendChild(size);
                }
                info.appendChild(meta);

                row.appendChild(iconWrap);
                row.appendChild(info);
                documentsBody.appendChild(row);
            });
        };

        document.querySelectorAll('.js-show-documents').forEach((button) => {
            button.addEventListener('click', () => {
                const documents = JSON.parse(button.dataset.documents || '[]');
                const mandatory = documents.filter((doc) => doc.mandatory);
                const optional = documents.filter((doc) => !doc.mandatory);

                document.getElementById('serviceDocumentsService').textContent = button.dataset.service + ' · ' + button.dataset.fee;
                document.getElementById('serviceDocumentsTotal').textContent = documents.length;
                document.getElementById('serviceDocumentsMandatory').textContent = mandatory.length;
                document.getElementById('serviceDocumentsOptional').textContent = optional.length;
                var editLink = document.getElementById('serviceDocumentsEdit');
                if (editLink) {
                    editLink.href = button.dataset.editUrl;
                }

                documentsBody.innerHTML = '';

                if (!documents.length) {
                    documentsBody.appendChild(makeEl('div', 'tm-muted small py-2', 'No documents are required for this service.'));
                } else {
                    renderGroup('Mandatory', '#101b3d', mandatory, 1);
                    renderGroup('Optional', '#6c757d', optional, mandatory.length + 1);
                }

            });
        });
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
        const PAGE_SIZE = 10;
        let currentPage = 1;
        const summary = document.getElementById('servicesPaginationSummary');
        const pages = document.getElementById('servicesPaginationPages');

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
            filterRows();
        };

        const renderPagination = (totalPages) => {
            pages.innerHTML = '';

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
                        filterRows(true);
                    }
                });
                item.appendChild(link);
                pages.appendChild(item);
            };

            add('‹', currentPage - 1, currentPage <= 1, false);
            for (let page = 1; page <= totalPages; page++) {
                add(String(page), page, false, page === currentPage);
            }
            add('›', currentPage + 1, currentPage >= totalPages, false);
        };

        function filterRows(keepPage) {
            if (keepPage !== true) {
                currentPage = 1;
            }

            const query = searchInput.value.trim().toLocaleLowerCase();
            const matched = [];

            rows.forEach((row) => {
                const matchesStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
                const matchesSearch = row.textContent.toLocaleLowerCase().includes(query);

                row.hidden = true;
                if (matchesStatus && matchesSearch) {
                    matched.push(row);
                }
            });

            const totalPages = Math.max(1, Math.ceil(matched.length / PAGE_SIZE));
            currentPage = Math.min(currentPage, totalPages);
            const start = (currentPage - 1) * PAGE_SIZE;
            matched.slice(start, start + PAGE_SIZE).forEach((row) => { row.hidden = false; });

            summary.textContent = matched.length
                ? 'Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, matched.length) + ' of ' + matched.length
                : 'No services found';
            renderPagination(totalPages);
        }

        searchInput.addEventListener('input', () => filterRows());
        sortSelect.addEventListener('change', sortRows);

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.filter;
                filterButtons.forEach((filterButton) => filterButton.classList.toggle('active', filterButton === button));
                filterRows();
            });
        });

        filterRows();
    });
</script>
@endsection