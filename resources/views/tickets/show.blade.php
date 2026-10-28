@extends('layouts.app')

@section('title', 'Service details — ' . config('app.name', 'Task Management'))

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
@php
    $canReassign = auth()->user()->can('reassign', \App\Models\Ticket::class);
    $canUpdateStatus = auth()->user()->can('updateStatus', $ticket);
    $canUploadDocument = auth()->user()->can('uploadDocument', $ticket);
    $canVerifyDocument = auth()->user()->can('verifyDocument', $ticket);

    $documentDotColor = function (string $status): string {
        return match ($status) {
            'Verified' => '#1f6b30',
            'Rejected' => '#7f1616',
            default => '#9aa1b0',
        };
    };
    $formatBytes = function (int $bytes): string {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format(max($bytes, 1) / 1024, 0).' KB';
    };
    $fileExtIcon = function (string $filename): array {
        $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'FILE';

        return match ($ext) {
            'PDF' => ['label' => 'PDF', 'bg' => '#fbe5ea', 'text' => '#c0392b'],
            'JPG', 'JPEG', 'PNG' => ['label' => $ext, 'bg' => '#ece5fb', 'text' => '#6d5bd0'],
            default => ['label' => $ext, 'bg' => '#eceef2', 'text' => '#6b7280'],
        };
    };
    $mandatoryCount = $ticket->service->documents->where('is_mandatory', true)->count();
    $optionalCount = $ticket->service->documents->where('is_mandatory', false)->count();
    $progressSteps = ['Documents', 'Verification', 'Work in progress', 'Submitted', 'Completed', 'Paid'];
    $progressIndex = match ($ticket->status) {
        \App\Enums\TicketStatus::DocumentsPending,
        \App\Enums\TicketStatus::PartiallyReceived,
        \App\Enums\TicketStatus::AdditionalDocumentsRequired => 0,
        \App\Enums\TicketStatus::DocumentsReceived,
        \App\Enums\TicketStatus::UnderVerification => 1,
        \App\Enums\TicketStatus::WorkInProgress,
        \App\Enums\TicketStatus::OnHold => 2,
        \App\Enums\TicketStatus::SubmittedToDepartment => 3,
        \App\Enums\TicketStatus::TaskCompleted => $ticket->paymentStatus() === \App\Enums\PaymentStatus::FullyPaid ? 5 : 4,
    };
@endphp

<div id="ticket-show-page">
    <x-page-header title="Service details" subtitle="Documents, status and payment for this service" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Services', 'url' => route('tickets.index')], ['label' => $ticket->number]]">
        <x-slot:actions>
            <a href="{{ route('tickets.index') }}" class="btn btn-tm-primary btn-sm px-3">&larr; My services</a>
        </x-slot:actions>
    </x-page-header>

    @php
        $paymentStatus = $ticket->paymentStatus();
        $paymentTone = match ($paymentStatus) {
            \App\Enums\PaymentStatus::FullyPaid => 'paid',
            \App\Enums\PaymentStatus::PartiallyPaid => 'part',
            default => 'unpaid',
        };
        $initials = fn (?string $name): string => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
        $daysOpen = (int) abs($ticket->created_at->diffInDays($ticket->completed_at ?? now()));
        $stepNote = 'Step '.($progressIndex + 1).' of '.count($progressSteps).($ticket->assignedTo && ! $ticket->status->isClosed() ? ' · being handled by '.$ticket->assignedTo->name : '');
    @endphp

    <section class="tm-card ticket-summary mb-3">
        <div class="ticket-summary-main">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div class="min-w-0">
                    <h1 class="ticket-service-title">{{ $ticket->service->name }}</h1>
                    <div class="ticket-service-details">
                        @if ($ticket->service->description)
                            {{ $ticket->service->description }}
                        @else
                            From enquiry <a href="{{ route('enquiries.show', $ticket->enquiry) }}" class="text-reset">{{ $ticket->enquiry->number }}</a> &middot; {{ $ticket->service->documents->count() }} {{ \Illuminate\Support\Str::plural('document', $ticket->service->documents->count()) }} required
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ticket-summary-badges">
                    <span class="ticket-number-chip">
                        {{ $ticket->number }}
                        <button type="button" class="ticket-copy-btn js-copy-number" data-copy="{{ $ticket->number }}" aria-label="Copy service number" title="Copy">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </span>
                    <span class="ticket-pill emp-pill-{{ \Illuminate\Support\Str::slug($ticket->status->value) }}">{{ $ticket->status->value }}</span>
                    <span class="ticket-pill ticket-pill-{{ $paymentTone }}">{{ $paymentStatus->value }}</span>
                </div>
            </div>
            <div class="ticket-facts">
                <div class="ticket-fact">
                    <div class="ticket-fact-label">Client</div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="ticket-avatar ticket-avatar-navy">{{ $initials($ticket->customer->name) }}</span>
                        <div class="min-w-0">
                            <a href="{{ route('customers.show', $ticket->customer) }}" class="ticket-fact-name text-decoration-none">{{ $ticket->customer->name }}</a>
                            @if ($ticket->customer->phone)
                                <div class="ticket-fact-line">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"></path></svg>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $ticket->customer->phone) }}" class="text-reset text-decoration-none">{{ $ticket->customer->phone }}</a>
                                </div>
                            @endif
                            @if ($ticket->customer->email)
                                <div class="ticket-fact-line">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><polyline points="3 7 12 13 21 7"></polyline></svg>
                                    <a href="mailto:{{ $ticket->customer->email }}" class="text-reset text-decoration-none">{{ $ticket->customer->email }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ticket-fact">
                    <div class="ticket-fact-label">Assigned to</div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="ticket-avatar ticket-avatar-green">{{ $ticket->assignedTo ? $initials($ticket->assignedTo->name) : '—' }}</span>
                        <div>
                            <div class="ticket-fact-name">{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</div>
                            @if ($canReassign)
                                <a href="#ticket-assignment-panel" class="ticket-fact-link">{{ $ticket->assignedTo ? 'Reassign' : 'Assign' }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ticket-fact">
                    <div class="ticket-fact-label">Created</div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="ticket-avatar ticket-avatar-grey">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </span>
                        <div>
                            <div class="ticket-fact-name">{{ $ticket->created_at->format('j M Y') }}</div>
                            <div class="ticket-fact-line">{{ $ticket->status->isClosed() ? 'Took' : 'Open' }} {{ $daysOpen }} {{ \Illuminate\Support\Str::plural('day', $daysOpen) }}</div>
                        </div>
                    </div>
                </div>

                <div class="ticket-fact">
                    <div class="ticket-fact-label">Fee</div>
                    <div class="ticket-fee-total">&#8377;{{ number_format((float) $ticket->total) }}</div>
                    <div class="ticket-fact-line">&#8377;{{ number_format((float) $ticket->price) }} + {{ rtrim(rtrim(number_format((float) $ticket->gst_percent, 2), '0'), '.') }}% GST &middot; <span class="ticket-fee-status ticket-fee-status-{{ $paymentTone }}">{{ $paymentStatus->value }}</span></div>
                </div>
            </div>
        </div>

        <div class="ticket-progress-wrap">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <span class="ticket-progress-label">Progress</span>
                <span class="ticket-progress-note">{{ $stepNote }}</span>
            </div>
            <div class="ticket-progress" aria-label="Service progress">
                @foreach ($progressSteps as $index => $step)
                    <div class="ticket-progress-step {{ $index < $progressIndex ? 'is-complete' : ($index === $progressIndex ? 'is-current' : '') }}">
                        <div class="ticket-step-track">
                            <span class="ticket-step-dot">@if ($index < $progressIndex)✓@else{{ $index + 1 }}@endif</span>
                            @if (! $loop->last)<span class="ticket-step-line"></span>@endif
                        </div>
                        <span>{{ $step }}</span>
                        @if ($index === $progressIndex)<span class="ticket-step-now">Now</span>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="tm-card p-0 mb-3 ticket-panel" id="ticket-documents-panel">
                <div class="ticket-panel-heading">
                    <h2>Documents ({{ $ticket->service->documents->count() }})</h2>
                    <span class="ticket-panel-meta">{{ $documentRows->filter(fn ($row) => $row['current']?->status === 'Verified')->count() }} of {{ $documentRows->count() }} verified</span>
                </div>
                @forelse ($documentRows as $row)
                    @php
                        $serviceDocument = $row['serviceDocument'];
                        $current = $row['current'];
                    @endphp
                    <div class="ticket-document-row d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-1 flex-shrink-0"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <div>
                                <div class="ticket-document-name">{{ $serviceDocument->name }}{{ $current ? ' v'.$current->version : '' }}</div>
                                @if ($current)
                                    <div class="ticket-document-note">
                                        {{ $current->version > 1 ? 'Re-uploaded' : 'Uploaded' }} by {{ $current->uploaderLabel() }} on service &middot; {{ $current->created_at->format('j M, g:i A') }}
                                    </div>
                                    @if ($current->text_value !== null)
                                        <div class="ticket-document-note u-f0afc2">Value: {{ $current->text_value }}</div>
                                    @endif
                                    @if ($current->status === 'Rejected' && $current->rejection_reason)
                                        <div class="ticket-document-note u-a8cc8a">Reason: {{ $current->rejection_reason }}</div>
                                    @endif
                                @else
                                    <div class="ticket-document-note">{{ $serviceDocument->is_mandatory ? 'Mandatory' : 'Optional' }} &middot; Not uploaded yet</div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center gap-1 u-7aa556">
                                <span class="rounded-circle d-inline-block" style="width:7px;height:7px;background:{{ $current ? $documentDotColor($current->status) : '#9aa1b0' }};"></span>
                                {{ $current?->status ?? 'Not uploaded' }}
                            </span>

                            @if ($current && $current->status === 'Awaiting check' && $canVerifyDocument)
                                <form method="POST" action="{{ route('tickets.documents.verify', [$ticket, $current]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success u-3898d2">Verify</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger js-reject-document u-3898d2" data-action="{{ route('tickets.documents.reject', [$ticket, $current]) }}">Reject</button>
                            @endif

                            @if ($current && $current->file_path)
                                <a href="{{ asset('storage/'.$current->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary u-3898d2">View</a>
                            @endif

                            @if ($row['versions']->count() > 1)
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary js-document-history u-3898d2"
                                   
                                    data-name="{{ $serviceDocument->name }}"
                                    data-mandatory="{{ $serviceDocument->is_mandatory ? 'Mandatory' : 'Optional' }}"
                                    data-service-document-id="{{ $serviceDocument->id }}"
                                    data-formats="{{ $serviceDocument->allowed_formats }}"
                                    data-max-size="{{ $serviceDocument->formattedMaxSize() }}"
                                    data-versions="{{ $row['versions']->map(fn ($v) => [
                                        'version' => $v->version,
                                        'status' => $v->status,
                                        'text' => $v->text_value,
                                        'filename' => $v->original_filename,
                                        'size' => $formatBytes($v->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($v->file_path) ? \Illuminate\Support\Facades\Storage::disk('public')->size($v->file_path) : 0),
                                        'icon' => $fileExtIcon((string) $v->original_filename),
                                        'uploaded_by' => $v->uploaderLabel(),
                                        'uploaded_at' => $v->created_at->format('j M Y, g:i A'),
                                        'verified_by' => $v->verifiedBy?->name,
                                        'verified_at' => $v->verified_at?->format('j M Y, g:i A'),
                                        'rejection_reason' => $v->rejection_reason,
                                        'url' => $v->file_path ? asset('storage/'.$v->file_path) : null,
                                        'verify_url' => route('tickets.documents.verify', [$ticket, $v]),
                                        'reject_url' => route('tickets.documents.reject', [$ticket, $v]),
                                    ])->toJson() }}"
                                >History</button>
                            @endif

                            @if ($canUploadDocument && (! $current || $current->status === 'Rejected'))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-tm-primary js-upload-document u-3898d2"
                                   
                                    data-bs-toggle="modal"
                                    data-bs-target="#uploadDocumentModal"
                                    data-service-document-id="{{ $serviceDocument->id }}"
                                    data-name="{{ $serviceDocument->name }}"
                                    data-input-type="{{ $serviceDocument->input_type }}"
                                    data-formats="{{ $serviceDocument->allowed_formats }}"
                                    data-max-size="{{ $serviceDocument->formattedMaxSize() }}"
                                >{{ $serviceDocument->isText() ? ($current ? 'Re-enter' : 'Enter') : ($current ? 'Re-upload' : 'Upload') }}</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-3 tm-muted small">No documents configured for this service.</div>
                @endforelse
            </section>

            @if ($canUpdateStatus)
                <section class="tm-card p-0 mb-3 ticket-panel" id="ticket-status-panel">
                    <div class="ticket-panel-heading ticket-panel-heading-green"><h2>Update status</h2></div>
                    @if ($ticket->status->isClosed())
                        <div class="alert alert-warning mb-0 mx-3 mt-3 u-5a305f">
                            After task completion, you can't change the status.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('tickets.update-status', $ticket) }}" class="p-3 ticket-status-form">
                        @csrf
                        @method('PATCH')
                        <fieldset {{ $ticket->status->isClosed() ? 'disabled' : '' }}>
                            <label class="form-label fw-semibold small">New status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select tm-field ticket-status-select @error('status') is-invalid @enderror">
                                @foreach (\App\Enums\TicketStatus::manualOptions() as $option)
                                    <option value="{{ $option->value }}" {{ $ticket->status === $option ? 'selected' : '' }}>{{ $option->value }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <label class="form-label fw-semibold small mt-3">Comment <span class="text-danger">*</span></label>
                            <textarea name="comment" required class="form-control tm-field ticket-comment @error('comment') is-invalid @enderror" rows="3" maxlength="2000" placeholder="What changed and why"></textarea>
                            @error('comment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="tm-muted">Comments become part of the service timeline.</small>
                                <button type="submit" class="btn btn-tm-primary btn-sm px-3">Save </button>
                            </div>
                        </fieldset>
                    </form>
                </section>
            @endif

            <section class="tm-card p-0 ticket-panel">
                <div class="ticket-panel-heading ticket-panel-heading-purple"><h2>Timeline</h2></div>
                <div class="ticket-timeline">
                    @forelse ($history->reverse() as $entry)
                        <div class="ticket-timeline-item">
                            <span class="ticket-timeline-dot"></span>
                            <div class="ticket-timeline-title">{{ $entry->details ?: $entry->action }}</div>
                            @if ($entry->details && $entry->action !== 'Updated')
                                <div class="ticket-timeline-desc">{{ $entry->details }}</div>
                            @endif
                            <div class="ticket-timeline-meta">{{ $entry->created_at->format('j M Y, g:i A') }} · {{ $entry->user?->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <p class="tm-muted small mb-0">No activity recorded yet.</p>
                    @endforelse
                </div>
            </section>
            
        </div>

        <div class="col-12 col-xl-4">
            

            <section class="tm-card p-0 mb-3 ticket-panel ticket-compact-content">
                <div class="ticket-panel-heading">
                    <h2>FeeS</h2>
                    @if ($ticket->paymentStatus() !== \App\Enums\PaymentStatus::FullyPaid)
                        <a href="{{ route('payments.index', ['enquiry' => $ticket->enquiry_id]) }}" class="badge rounded-pill text-bg-primary fw-normal px-2 py-1 text-decoration-none u-dcc109">Record payment</a>
                    @endif
                </div>
                <div class="ticket-info-row"><span class="tm-muted">Price</span><strong>&#8377;{{ number_format((float) $ticket->price, 2) }}</strong></div>
                <div class="ticket-info-row"><span class="tm-muted">GST ({{ number_format((float) $ticket->gst_percent, 0) }}%)</span><strong>Included</strong></div>
                <div class="ticket-fee-pending rounded d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <strong>Payment</strong>
                        <div class="small">{{ $ticket->paymentStatus()->value }}</div>
                        @if ($ticket->enquiry->tickets->count() > 1)
                            <div class="small tm-muted">Shared across {{ $ticket->enquiry->tickets->count() }} services in {{ $ticket->enquiry->number }}</div>
                        @endif
                    </div>
                    <strong class="fs-5">&#8377;{{ number_format($ticket->paymentStatus() === \App\Enums\PaymentStatus::FullyPaid ? (float) $ticket->enquiry->total : $ticket->balanceDue(), 2) }}</strong>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 ticket-panel ticket-compact-content" id="ticket-assignment-panel">
                <div class="ticket-panel-heading"><h2>Assignment</h2></div>
                <div class="p-3">
                    <div class="small mb-2">Currently assigned to <strong>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</strong></div>
                    @if ($canReassign)
                        <form method="POST" action="{{ route('tickets.reassign', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <select name="assigned_to" class="form-select tm-field mb-2" aria-label="Assign service to">
                                <option value="">Unassigned</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ $ticket->assigned_to === $employee->id ? 'selected' : '' }}>{{ $employee->role->label() === 'Employee' ? $employee->name : $employee->role->label().' — '.$employee->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Reassign</button>
                        </form>
                    @else
                        <div class="small tm-muted">Only an Admin can reassign a service.</div>
                    @endif
                </div>
            </section>

            <section class="tm-card p-0 ticket-panel ticket-compact-content">
                <div class="ticket-panel-heading ticket-panel-heading-teal"><h2>Same enquiry</h2></div>
                <div class="ticket-enquiry-row">
                    <a class="ticket-document-name text-decoration-none" href="{{ route('enquiries.show', $ticket->enquiry) }}">{{ $ticket->enquiry->number }}</a>
                    <div class="ticket-document-note">{{ $ticket->enquiry->created_at->format('j M Y') }} · {{ $ticket->enquiry->status }}</div>
                </div>
                <div class="ticket-info-row"><span class="tm-muted">Enquiry total</span><strong>&#8377;{{ number_format((float) $ticket->enquiry->total, 2) }}</strong></div>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content u-d581ba">
            <form method="POST" action="{{ route('tickets.documents.store', $ticket) }}" enctype="multipart/form-data" id="uploadDocumentForm">
                @csrf
                <input type="hidden" name="service_document_id" id="uploadDocServiceDocumentId">
                <div class="p-2 px-3 u-8b012c">
                    <h2 class="h6 fw-bold mb-0 text-white" id="uploadDocTitle">Upload document</h2>
                </div>
                <div class="modal-body p-4">
                    <div id="uploadDocFileField">
                        <label class="tm-field-label d-block">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control tm-field" id="uploadDocFileInput" required>
                        <div class="form-text tm-muted u-11d1e8" id="uploadDocHint"></div>
                    </div>
                    <div id="uploadDocTextField" class="d-none">
                        <label class="tm-field-label d-block">Details <span class="text-danger">*</span></label>
                        <textarea name="text_value" class="form-control tm-field" id="uploadDocTextInput" rows="3" maxlength="2000" disabled></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-tm-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content u-d581ba">
            <form method="POST" id="rejectDocumentForm">
                @csrf
                @method('PATCH')
                <div class="p-3 u-d75e14">
                    <h2 class="h6 fw-bold mb-0 text-white">Reject document</h2>
                </div>
                <div class="modal-body p-4">
                    <label class="tm-field-label d-block">Reason <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control tm-field" rows="3" maxlength="500" required placeholder="What's wrong with this document?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="documentHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered u-131429">
        <div class="modal-content u-d581ba">
            <div class="d-flex align-items-start gap-3 p-3 u-8b012c">
                <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 u-dd39d6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </span>
                <div class="flex-grow-1">
                    <h2 class="h6 fw-bold mb-0 text-white"><span id="documentHistoryTitle"></span> &middot; document history</h2>
                    <div class="small u-5bb556">{{ $ticket->number }} &middot; {{ $ticket->service->name }} &middot; <span id="documentHistoryMandatory"></span></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="d-flex u-1616f0">
                <div class="flex-grow-1 text-center py-3 u-350541">
                    <div class="tm-muted u-31578e">Versions</div>
                    <div class="fw-bold mb-0 u-5a305f" id="documentHistoryVersionsCount"></div>
                </div>
                <div class="flex-grow-1 text-center py-3 u-350541">
                    <div class="tm-muted u-31578e">Rejections</div>
                    <div class="fw-bold mb-0 u-ea6dd7" id="documentHistoryRejectionsCount"></div>
                </div>
                <div class="flex-grow-1 text-center py-3">
                    <div class="tm-muted u-31578e">Status now</div>
                    <div class="fw-bold mb-0 u-5a305f" id="documentHistoryStatusNow"></div>
                </div>
            </div>

            <div class="modal-body p-4 u-8fa6ab" id="documentHistoryBody"></div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-primary js-upload-new-version">&uarr; Upload new version</button>
                <button type="button" class="btn btn-tm-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    (function () {
        var assignSelect = document.querySelector('select[name="assigned_to"]');
        if (assignSelect && window.TomSelect) {
            new TomSelect(assignSelect, { create: false, allowEmptyOption: true, placeholder: 'Search employee...', dropdownParent: 'body' });
        }

        var canVerifyDocument = @json($canVerifyDocument);
        var csrfToken = document.querySelector('#uploadDocumentForm input[name="_token"]').value;

        function openUploadModal(serviceDocumentId, name, formats, maxSize, isText) {
            document.getElementById('uploadDocServiceDocumentId').value = serviceDocumentId;
            document.getElementById('uploadDocTitle').textContent = (isText ? 'Enter ' : 'Upload ') + name;

            document.getElementById('uploadDocFileField').classList.toggle('d-none', isText);
            document.getElementById('uploadDocTextField').classList.toggle('d-none', !isText);
            document.getElementById('uploadDocFileInput').disabled = isText;
            document.getElementById('uploadDocFileInput').required = !isText;
            document.getElementById('uploadDocTextInput').disabled = !isText;
            document.getElementById('uploadDocTextInput').required = isText;

            var hint = [];
            if (formats) { hint.push('Allowed: ' + formats); }
            if (maxSize) { hint.push('Max size: ' + maxSize); }
            document.getElementById('uploadDocHint').textContent = hint.join(' · ');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadDocumentModal')).show();
        }

        document.querySelectorAll('.js-upload-document').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openUploadModal(
                    btn.getAttribute('data-service-document-id'),
                    btn.getAttribute('data-name'),
                    btn.getAttribute('data-formats'),
                    btn.getAttribute('data-max-size'),
                    btn.getAttribute('data-input-type') === 'text'
                );
            });
        });

        var copyNumber = document.querySelector('.js-copy-number');
        if (copyNumber) {
            copyNumber.addEventListener('click', function () {
                if (navigator.clipboard) { navigator.clipboard.writeText(copyNumber.getAttribute('data-copy')); }
                copyNumber.classList.add('is-copied');
                setTimeout(function () { copyNumber.classList.remove('is-copied'); }, 1200);
            });
        }

        var rejectForm = document.getElementById('rejectDocumentForm');

        function openRejectModal(action) {
            rejectForm.action = action;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectDocumentModal')).show();
        }

        document.querySelectorAll('.js-reject-document').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openRejectModal(btn.getAttribute('data-action'));
            });
        });

        var historyBody = document.getElementById('documentHistoryBody');
        var historyTitle = document.getElementById('documentHistoryTitle');
        var historyMandatory = document.getElementById('documentHistoryMandatory');
        var historyVersionsCount = document.getElementById('documentHistoryVersionsCount');
        var historyRejectionsCount = document.getElementById('documentHistoryRejectionsCount');
        var historyStatusNow = document.getElementById('documentHistoryStatusNow');
        var uploadNewVersionBtn = document.querySelector('.js-upload-new-version');
        var currentHistoryDoc = null;

        function statusColor(status) {
            return status === 'Verified' ? '#1f6b30' : (status === 'Rejected' ? '#7f1616' : '#9aa1b0');
        }

        function escapeHtml(value) {
            return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function versionCard(v, isCurrent) {
            var badge = isCurrent
                ? '<span class="badge rounded-pill text-bg-primary fw-normal ms-2 u-87458d">CURRENT</span>'
                : '';

            var isTextVersion = v.text !== null && v.text !== undefined;
            var actions = isTextVersion ? '' : '<a href="' + v.url + '" target="_blank" class="btn btn-sm btn-outline-secondary u-3898d2">View</a>'
                + '<a href="' + v.url + '" download class="btn btn-sm btn-outline-secondary u-3898d2">Download</a>';

            var verifyActions = '';
            if (isCurrent && v.status === 'Awaiting check' && canVerifyDocument) {
                verifyActions = '<button type="button" class="btn btn-sm btn-outline-danger js-inline-reject u-3898d2" data-action="' + v.reject_url + '">Reject</button>'
                    + '<form method="POST" action="' + v.verify_url + '" class="d-inline">'
                    + '<input type="hidden" name="_token" value="' + csrfToken + '">'
                    + '<input type="hidden" name="_method" value="PATCH">'
                    + '<button type="submit" class="btn btn-sm btn-success u-3898d2">&check; Verify</button>'
                    + '</form>';
            }

            return '<div class="rounded-3 p-3 mb-2" style="' + (isCurrent ? 'background:#eef4ff;border:1px solid #d7e6ff;' : 'background:#fff;border:1px solid #edf0f4;') + '">'
                + '<div class="d-flex justify-content-between align-items-start gap-2 mb-1">'
                + '<div class="d-flex align-items-center">'
                + '<strong>Version ' + v.version + '</strong>' + badge
                + '</div>'
                + '<span class="small fw-semibold" style="color:' + statusColor(v.status) + ';">&bull; ' + v.status + '</span>'
                + '</div>'
                + '<div class="d-flex align-items-start gap-2 mb-2">'
                + '<span class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;background:' + v.icon.bg + ';color:' + v.icon.text + ';font-size:.6rem;font-weight:700;">' + v.icon.label + '</span>'
                + '<div>'
                + '<div class="small">' + (isTextVersion ? escapeHtml(v.text) : v.filename + ' &middot; ' + v.size) + '</div>'
                + '<div class="tm-muted u-fde308">Uploaded by <strong>' + v.uploaded_by + '</strong> (Staff) via service &middot; ' + v.uploaded_at + '</div>'
                + '</div>'
                + '</div>'
                + '<div class="d-flex align-items-center gap-2">' + actions + verifyActions + '</div>'
                + '</div>';
        }

        function rejectionNotice(v) {
            return '<div class="mb-2">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<strong class="u-9838f4">Version ' + v.version + ' rejected</strong>'
                + '<span class="tm-muted u-fde308">' + (v.verified_at || '') + '</span>'
                + '</div>'
                + '<div class="small tm-muted mb-1">by <strong>' + (v.verified_by || 'Staff') + '</strong> (Staff)</div>'
                + '<div class="rounded-3 p-2 u-21376f"><strong>Reason:</strong> ' + v.rejection_reason + '</div>'
                + '</div>';
        }

        document.querySelectorAll('.js-document-history').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var versions = JSON.parse(btn.getAttribute('data-versions'));

                currentHistoryDoc = {
                    serviceDocumentId: btn.getAttribute('data-service-document-id'),
                    name: btn.getAttribute('data-name'),
                    formats: btn.getAttribute('data-formats'),
                    maxSize: btn.getAttribute('data-max-size'),
                };

                historyTitle.textContent = btn.getAttribute('data-name');
                historyMandatory.textContent = btn.getAttribute('data-mandatory');
                historyVersionsCount.textContent = versions.length;
                historyRejectionsCount.textContent = versions.filter(function (v) { return v.status === 'Rejected'; }).length;
                historyStatusNow.textContent = versions[0].status;
                historyStatusNow.style.color = statusColor(versions[0].status);

                historyBody.innerHTML = versions.map(function (v, index) {
                    var isCurrent = index === 0;
                    var html = '';
                    if (!isCurrent && v.status === 'Rejected') {
                        html += rejectionNotice(v);
                    }
                    html += versionCard(v, isCurrent);
                    return html;
                }).join('');

                bootstrap.Modal.getOrCreateInstance(document.getElementById('documentHistoryModal')).show();
            });
        });

        historyBody.addEventListener('click', function (event) {
            var btn = event.target.closest('.js-inline-reject');
            if (btn) {
                bootstrap.Modal.getInstance(document.getElementById('documentHistoryModal'))?.hide();
                openRejectModal(btn.getAttribute('data-action'));
            }
        });

        uploadNewVersionBtn.addEventListener('click', function () {
            if (!currentHistoryDoc) { return; }
            bootstrap.Modal.getInstance(document.getElementById('documentHistoryModal'))?.hide();
            openUploadModal(currentHistoryDoc.serviceDocumentId, currentHistoryDoc.name, currentHistoryDoc.formats, currentHistoryDoc.maxSize);
        });
    })();
</script>
@endpush
