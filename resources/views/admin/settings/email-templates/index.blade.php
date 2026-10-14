@extends('layouts.app')

@section('title', 'Email Templates — ' . config('app.name', 'Task Management'))

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    #emailTemplatesTable.tm-table thead th:first-child,
    #emailTemplatesTable.tm-table thead th:last-child {
        border-radius: 0;
    }
    #emailTemplatesTable.dataTable {
        border-collapse: collapse !important;
    }
    #emailTemplatesTable tbody td,
    #emailTemplatesTable tbody td code {
        font-size: .8rem;
    }
</style>
@endpush

@section('content')
<x-page-header title="Settings" subtitle="Firm profile, branding and system preferences" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Email Templates']]" />

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.firm-profile') }}">Firm Profile</a></li>
    <li class="nav-item"><span class="nav-link active">Email Templates</span></li>
</ul>

<div class="tm-card p-0">
    <div class="p-3">
        <input type="search" id="emailTemplatesSearch" class="form-control form-control-sm tm-field" placeholder="Search by template key or subject" aria-label="Search email templates" style="max-width: 320px;">
    </div>

    <div class="table-responsive">
        <table class="table tm-table align-middle mb-0 w-100" id="emailTemplatesTable">
            <thead>
                <tr>
                    <th>Template key</th>
                    <th>Subject</th>
                    <th class="text-end no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($templates as $template)
                    <tr>
                        <td class="fw-semibold"><code>{{ $template->key }}</code></td>
                        <td>{{ $template->subject }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.settings.email-templates.edit', $template) }}" class="tm-icon-btn-sm text-decoration-none" aria-label="Edit {{ $template->key }}" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4Z"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(function () {
        var table = $('#emailTemplatesTable').DataTable({
            dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center px-3 py-3"i<"d-flex align-items-center gap-3"p>>',
            pageLength: 10,
            autoWidth: false,
            searching: true,
            order: [[0, 'asc']],
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ templates',
                infoEmpty: 'Showing 0 of 0 templates',
                paginate: { previous: '‹', next: '›' },
                emptyTable: '<div class="text-center py-5"><p class="fw-semibold mb-1">No templates yet</p></div>',
                zeroRecords: '<div class="text-center py-5"><p class="fw-semibold mb-1">No templates found</p><p class="tm-muted small mb-0">Try a different search.</p></div>',
            },
            columnDefs: [
                { targets: [2], orderable: false, searchable: false },
            ],
        });

        $('#emailTemplatesSearch').on('keyup input', function () {
            table.search(this.value).draw();
        });
    });
</script>
@endpush
