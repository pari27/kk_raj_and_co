@extends('layouts.app')

@section('title', 'Ticket details — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    #ticket-show-page .ticket-summary { border-left: 5px solid #29995a; overflow: hidden; }
    #ticket-show-page .ticket-summary-main { padding: 1.25rem 1.5rem; }
    #ticket-show-page .ticket-number { color: #596579; font-size: .8rem; font-weight: 700; }
    #ticket-show-page .ticket-service-title { color: #17233d; font-size: 1.1rem; font-weight: 700; margin: .45rem 0 .6rem; }
    #ticket-show-page .ticket-meta { color: #596579; display: flex; flex-wrap: wrap; gap: .45rem 1rem; font-size: .78rem; }
    #ticket-show-page .ticket-progress-wrap { border-top: 1px solid #edf0f4; padding: 1rem 1.25rem .9rem; overflow-x: auto; }
    #ticket-show-page .ticket-progress { display: flex; align-items: flex-start; }
    #ticket-show-page .ticket-progress-step { flex: 1 1 0; min-width: 0; text-align: center; color: #8b95a7; font-size: .6rem; font-weight: 600; }
    #ticket-show-page .ticket-step-track { display: flex; align-items: center; justify-content: center; position: relative; margin-bottom: .3rem; }
    #ticket-show-page .ticket-step-dot { width: 1.15rem; height: 1.15rem; flex: 0 0 1.15rem; display: grid; place-items: center; border: 2px solid #d8dee8; border-radius: 50%; background: #fff; color: #7b8798; font-size: .58rem; position: relative; z-index: 1; }
    #ticket-show-page .ticket-step-line { position: absolute; left: calc(50% + .575rem); width: calc(100% - 1.15rem); top: 50%; transform: translateY(-50%); height: 2px; background: #e2e6ed; }
    #ticket-show-page .ticket-progress-step.is-complete { color: #217043; }
    #ticket-show-page .ticket-progress-step.is-complete .ticket-step-dot { color: #fff; background: #29995a; border-color: #29995a; }
    #ticket-show-page .ticket-progress-step.is-complete .ticket-step-line { background: #29995a; }
    #ticket-show-page .ticket-progress-step.is-current { color: #805d00; }
    #ticket-show-page .ticket-progress-step.is-current .ticket-step-dot { border-color: #c58b00; color: #c58b00; background: #fff8df; }
    #ticket-show-page .ticket-panel { overflow: hidden; }
    #ticket-show-page .ticket-panel-heading { align-items: center; background: #101b3d; color: #fff; display: flex; justify-content: space-between; padding: .8rem 1rem; }
    #ticket-show-page .ticket-panel-heading h2 { font-size: .95rem; font-weight: 700; margin: 0; }
    #ticket-show-page .ticket-panel-heading-green { background: #101b3d; }
    #ticket-show-page .ticket-panel-heading-teal { background: #1f6b30; }
    #ticket-show-page .ticket-panel-heading-purple { background: #101b3d; }
    #ticket-show-page .ticket-panel-meta { color: #d5dceb; font-size: .75rem; }
    #ticket-show-page .ticket-panel-heading a.badge.text-bg-primary:hover { background-color: #0b5ed7 !important; color: #fff; }
    #ticket-show-page .ticket-document-row, #ticket-show-page .ticket-enquiry-row { border-bottom: 1px solid #edf0f4; padding: .8rem 1rem; }
    #ticket-show-page .ticket-document-row:last-child, #ticket-show-page .ticket-enquiry-row:last-child { border-bottom: 0; }
    #ticket-show-page .ticket-document-name { color: #202b3e; font-size: .82rem; font-weight: 700; }
    #ticket-show-page .ticket-document-note, #ticket-show-page .ticket-timeline-meta { color: #667287; font-size: .72rem; margin-top: .2rem; }
    #ticket-show-page .ticket-timeline-desc { color: #000; font-size: .72rem; }
    #ticket-show-page .ticket-status-form, #ticket-show-page .ticket-status-form .form-label, #ticket-show-page .ticket-status-form .form-select, #ticket-show-page .ticket-status-form .form-control, #ticket-show-page .ticket-status-form .invalid-feedback, #ticket-show-page .ticket-status-form small, #ticket-show-page .ticket-status-form .btn { font-size: .8rem; }
    #ticket-show-page .ticket-timeline { padding: 1rem; }
    #ticket-show-page .ticket-timeline-item { border-left: 2px solid #e0e5ed; margin-left: .45rem; padding: 0 0 .5rem 1rem; position: relative; }
    #ticket-show-page .ticket-timeline-item:last-child { border-left-color: transparent; padding-bottom: 0; }
    #ticket-show-page .ticket-timeline-dot { background: #24569b; border: 3px solid #e7effc; border-radius: 50%; height: .8rem; left: -.48rem; position: absolute; top: .05rem; width: .8rem; }
    #ticket-show-page .ticket-timeline-item:first-child .ticket-timeline-dot { background: #24569b; border-color: #e7effc; }
    #ticket-show-page .ticket-info-row { align-items: center; border-bottom: 1px solid #edf0f4; display: flex; font-size: .8rem; justify-content: space-between; gap: .75rem; padding: .7rem 1rem; }
    #ticket-show-page .ticket-info-row:last-child { border-bottom: 0; }
    #ticket-show-page .ticket-compact-content .ticket-info-row,
    #ticket-show-page .ticket-compact-content .ticket-fee-pending,
    #ticket-show-page .ticket-compact-content .ticket-document-name,
    #ticket-show-page .ticket-compact-content .ticket-document-note,
    #ticket-show-page .ticket-compact-content .small { font-size: .8rem; }
    #ticket-show-page .ticket-compact-content .fs-5 { font-size: .8rem !important; }
    #ticket-show-page .ticket-fee-pending { background: #fceaea; color: #982424; margin: .8rem 1rem 1rem; padding: .8rem; }
    #ticket-show-page .btn-outline-navy { color: #101b3d; border-color: #101b3d; }
    #ticket-show-page .btn-outline-navy:hover { color: #fff; background: #101b3d; border-color: #101b3d; }
    #documentHistoryModal .modal-content { font-size: .8rem; }
    @media (max-width: 575.98px) {
        #ticket-show-page .ticket-summary-main { padding: 1rem; }
        #ticket-show-page .ticket-progress { min-width: 34rem; }
        #ticket-show-page .ticket-panel-heading { padding: .75rem .85rem; }
    }
</style>
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
    <x-page-header title="Ticket details" subtitle="Documents, status and payment for this ticket" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Tickets', 'url' => route('tickets.index')], ['label' => $ticket->number]]">
        <x-slot:actions>
            <a href="{{ route('tickets.index') }}" class="btn btn-tm-primary btn-sm px-3">&larr; My tickets</a>
        </x-slot:actions>
    </x-page-header>

    <section class="tm-card ticket-summary mb-3">
        <div class="ticket-summary-main">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="ticket-number">{{ $ticket->number }}</span>
                <x-status-badge :status="$ticket->status->value" />
                <x-payment-status-badge :status="$ticket->paymentStatus()" />
            </div>
            <h1 class="ticket-service-title">{{ $ticket->service->name }}</h1>
            <div class="ticket-meta">
                <strong class="text-dark">{{ $ticket->customer->name }}</strong>
                @if ($ticket->customer->phone)
                    <span>{{ $ticket->customer->phone }}</span>
                @endif
                @if ($ticket->customer->email)
                    <span>{{ $ticket->customer->email }}</span>
                @endif
                <span>Assigned to <strong class="text-dark">{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</strong></span>
                <span>Created {{ $ticket->created_at->format('j M Y') }} · {{ $ticket->created_at->diffForHumans() }}</span>
            </div>
        </div>
        <div class="ticket-progress-wrap">
            <div class="ticket-progress" aria-label="Ticket progress">
                @foreach ($progressSteps as $index => $step)
                    <div class="ticket-progress-step {{ $index < $progressIndex ? 'is-complete' : ($index === $progressIndex ? 'is-current' : '') }}">
                        <div class="ticket-step-track">
                            <span class="ticket-step-dot">@if ($index < $progressIndex)✓@elseif ($index === $progressIndex)●@endif</span>
                            @if (! $loop->last)<span class="ticket-step-line"></span>@endif
                        </div>
                        <span>{{ $step }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="tm-card p-0 mb-3 ticket-panel">
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
                                        {{ $current->version > 1 ? 'Re-uploaded' : 'Uploaded' }} by {{ $current->uploadedBy?->name ?? 'Staff' }} on ticket &middot; {{ $current->created_at->format('j M, g:i A') }}
                                    </div>
                                    @if ($current->status === 'Rejected' && $current->rejection_reason)
                                        <div class="ticket-document-note" style="color: #7f1616;">Reason: {{ $current->rejection_reason }}</div>
                                    @endif
                                @else
                                    <div class="ticket-document-note">{{ $serviceDocument->is_mandatory ? 'Mandatory' : 'Optional' }} &middot; Not uploaded yet</div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center gap-1" style="font-size: .72rem; color: #000;">
                                <span class="rounded-circle d-inline-block" style="width:7px;height:7px;background:{{ $current ? $documentDotColor($current->status) : '#9aa1b0' }};"></span>
                                {{ $current?->status ?? 'Not uploaded' }}
                            </span>

                            @if ($current && $current->status === 'Awaiting check' && $canVerifyDocument)
                                <form method="POST" action="{{ route('tickets.documents.verify', [$ticket, $current]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success" style="font-size: .72rem; padding: .25rem .6rem;">Verify</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger js-reject-document" style="font-size: .72rem; padding: .25rem .6rem;" data-action="{{ route('tickets.documents.reject', [$ticket, $current]) }}">Reject</button>
                            @endif

                            @if ($current)
                                <a href="{{ asset('storage/'.$current->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: .72rem; padding: .25rem .6rem;">View</a>
                            @endif

                            @if ($row['versions']->count() > 1)
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary js-document-history"
                                    style="font-size: .72rem; padding: .25rem .6rem;"
                                    data-name="{{ $serviceDocument->name }}"
                                    data-mandatory="{{ $serviceDocument->is_mandatory ? 'Mandatory' : 'Optional' }}"
                                    data-service-document-id="{{ $serviceDocument->id }}"
                                    data-formats="{{ $serviceDocument->allowed_formats }}"
                                    data-max-size="{{ $serviceDocument->formattedMaxSize() }}"
                                    data-versions="{{ $row['versions']->map(fn ($v) => [
                                        'version' => $v->version,
                                        'status' => $v->status,
                                        'filename' => $v->original_filename,
                                        'size' => $formatBytes(\Illuminate\Support\Facades\Storage::disk('public')->exists($v->file_path) ? \Illuminate\Support\Facades\Storage::disk('public')->size($v->file_path) : 0),
                                        'icon' => $fileExtIcon($v->original_filename),
                                        'uploaded_by' => $v->uploadedBy?->name ?? 'Staff',
                                        'uploaded_at' => $v->created_at->format('j M Y, g:i A'),
                                        'verified_by' => $v->verifiedBy?->name,
                                        'verified_at' => $v->verified_at?->format('j M Y, g:i A'),
                                        'rejection_reason' => $v->rejection_reason,
                                        'url' => asset('storage/'.$v->file_path),
                                        'verify_url' => route('tickets.documents.verify', [$ticket, $v]),
                                        'reject_url' => route('tickets.documents.reject', [$ticket, $v]),
                                    ])->toJson() }}"
                                >History</button>
                            @endif

                            @if ($canUploadDocument && (! $current || $current->status === 'Rejected'))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-tm-primary js-upload-document"
                                    style="font-size: .72rem; padding: .25rem .6rem;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#uploadDocumentModal"
                                    data-service-document-id="{{ $serviceDocument->id }}"
                                    data-name="{{ $serviceDocument->name }}"
                                    data-formats="{{ $serviceDocument->allowed_formats }}"
                                    data-max-size="{{ $serviceDocument->formattedMaxSize() }}"
                                >{{ $current ? 'Re-upload' : 'Upload' }}</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-3 tm-muted small">No documents configured for this service.</div>
                @endforelse
            </section>

            @if ($canUpdateStatus)
                <section class="tm-card p-0 mb-3 ticket-panel">
                    <div class="ticket-panel-heading ticket-panel-heading-green"><h2>Update status</h2></div>
                    @if ($ticket->status->isClosed())
                        <div class="alert alert-warning mb-0 mx-3 mt-3" style="font-size: .8rem;">
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
                            <label class="form-label fw-semibold small mt-3">Comment (optional)</label>
                            <textarea name="comment" class="form-control tm-field ticket-comment" rows="3" maxlength="2000" placeholder="What changed and why"></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="tm-muted">Comments become part of the ticket timeline.</small>
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
                        <a href="{{ route('payments.index', ['enquiry' => $ticket->enquiry_id]) }}" class="badge rounded-pill text-bg-primary fw-normal px-2 py-1 text-decoration-none" style="font-size: .68rem;">Make Payment</a>
                    @endif
                </div>
                <div class="ticket-info-row"><span class="tm-muted">Price</span><strong>&#8377;{{ number_format((float) $ticket->price, 2) }}</strong></div>
                <div class="ticket-info-row"><span class="tm-muted">GST ({{ number_format((float) $ticket->gst_percent, 0) }}%)</span><strong>Included</strong></div>
                <div class="ticket-fee-pending rounded d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <strong>Payment</strong>
                        <div class="small">{{ $ticket->paymentStatus()->value }}</div>
                        @if ($ticket->enquiry->tickets->count() > 1)
                            <div class="small tm-muted">Shared across {{ $ticket->enquiry->tickets->count() }} tickets in {{ $ticket->enquiry->number }}</div>
                        @endif
                    </div>
                    <strong class="fs-5">&#8377;{{ number_format($ticket->paymentStatus() === \App\Enums\PaymentStatus::FullyPaid ? (float) $ticket->enquiry->total : $ticket->balanceDue(), 2) }}</strong>
                </div>
            </section>

            <section class="tm-card p-0 mb-3 ticket-panel ticket-compact-content">
                <div class="ticket-panel-heading"><h2>Assignment</h2></div>
                <div class="p-3">
                    <div class="small mb-2">Currently assigned to <strong>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</strong></div>
                    @if ($canReassign)
                        <form method="POST" action="{{ route('tickets.reassign', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <select name="assigned_to" class="form-select tm-field mb-2" aria-label="Assign ticket to">
                                <option value="">Unassigned</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ $ticket->assigned_to === $employee->id ? 'selected' : '' }}>{{ $employee->role->label() === 'Employee' ? $employee->name : $employee->role->label().' � '.$employee->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Reassign</button>
                        </form>
                    @else
                        <div class="small tm-muted">Only Admin/Super Admin can reassign a ticket.</div>
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
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <form method="POST" action="{{ route('tickets.documents.store', $ticket) }}" enctype="multipart/form-data" id="uploadDocumentForm">
                @csrf
                <input type="hidden" name="service_document_id" id="uploadDocServiceDocumentId">
                <div class="p-3" style="background: #101b3d;">
                    <h2 class="h6 fw-bold mb-0 text-white" id="uploadDocTitle">Upload document</h2>
                </div>
                <div class="modal-body p-4">
                    <label class="tm-field-label d-block">File <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control tm-field" required>
                    <div class="form-text tm-muted" id="uploadDocHint"></div>
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
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <form method="POST" id="rejectDocumentForm">
                @csrf
                @method('PATCH')
                <div class="p-3" style="background: #7f1616;">
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
    <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
        <div class="modal-content" style="border: 0; border-radius: .75rem; overflow: hidden;">
            <div class="d-flex align-items-start gap-3 p-3" style="background: #101b3d;">
                <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: rgba(255,255,255,.12); color: #fff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </span>
                <div class="flex-grow-1">
                    <h2 class="h6 fw-bold mb-0 text-white"><span id="documentHistoryTitle"></span> &middot; document history</h2>
                    <div class="small" style="color: rgba(255,255,255,.7);">{{ $ticket->number }} &middot; {{ $ticket->service->name }} &middot; <span id="documentHistoryMandatory"></span></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="d-flex" style="border-bottom: 1px solid #edf0f4;">
                <div class="flex-grow-1 text-center py-3" style="border-right: 1px solid #edf0f4;">
                    <div class="tm-muted" style="font-size: .72rem;">Versions</div>
                    <div class="fw-bold mb-0" style="font-size: .8rem;" id="documentHistoryVersionsCount"></div>
                </div>
                <div class="flex-grow-1 text-center py-3" style="border-right: 1px solid #edf0f4;">
                    <div class="tm-muted" style="font-size: .72rem;">Rejections</div>
                    <div class="fw-bold mb-0" style="color: #7f1616; font-size: .8rem;" id="documentHistoryRejectionsCount"></div>
                </div>
                <div class="flex-grow-1 text-center py-3">
                    <div class="tm-muted" style="font-size: .72rem;">Status now</div>
                    <div class="fw-bold mb-0" style="font-size: .8rem;" id="documentHistoryStatusNow"></div>
                </div>
            </div>

            <div class="modal-body p-4" id="documentHistoryBody" style="max-height: 55vh; overflow-y: auto;"></div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-primary js-upload-new-version">&uarr; Upload new version</button>
                <button type="button" class="btn btn-tm-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var canVerifyDocument = @json($canVerifyDocument);
        var csrfToken = document.querySelector('#uploadDocumentForm input[name="_token"]').value;

        function openUploadModal(serviceDocumentId, name, formats, maxSize) {
            document.getElementById('uploadDocServiceDocumentId').value = serviceDocumentId;
            document.getElementById('uploadDocTitle').textContent = 'Upload ' + name;

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
                    btn.getAttribute('data-max-size')
                );
            });
        });

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

        function versionCard(v, isCurrent) {
            var badge = isCurrent
                ? '<span class="badge rounded-pill text-bg-primary fw-normal ms-2" style="font-size: .65rem;">CURRENT</span>'
                : '';

            var actions = '<a href="' + v.url + '" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: .72rem; padding: .25rem .6rem;">View</a>'
                + '<a href="' + v.url + '" download class="btn btn-sm btn-outline-secondary" style="font-size: .72rem; padding: .25rem .6rem;">Download</a>';

            var verifyActions = '';
            if (isCurrent && v.status === 'Awaiting check' && canVerifyDocument) {
                verifyActions = '<button type="button" class="btn btn-sm btn-outline-danger js-inline-reject" data-action="' + v.reject_url + '" style="font-size: .72rem; padding: .25rem .6rem;">Reject</button>'
                    + '<form method="POST" action="' + v.verify_url + '" class="d-inline">'
                    + '<input type="hidden" name="_token" value="' + csrfToken + '">'
                    + '<input type="hidden" name="_method" value="PATCH">'
                    + '<button type="submit" class="btn btn-sm btn-success" style="font-size: .72rem; padding: .25rem .6rem;">&check; Verify</button>'
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
                + '<div class="small">' + v.filename + ' &middot; ' + v.size + '</div>'
                + '<div class="tm-muted" style="font-size:.75rem;">Uploaded by <strong>' + v.uploaded_by + '</strong> (Staff) via ticket &middot; ' + v.uploaded_at + '</div>'
                + '</div>'
                + '</div>'
                + '<div class="d-flex align-items-center gap-2">' + actions + verifyActions + '</div>'
                + '</div>';
        }

        function rejectionNotice(v) {
            return '<div class="mb-2">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<strong style="color:#7f1616;">Version ' + v.version + ' rejected</strong>'
                + '<span class="tm-muted" style="font-size:.75rem;">' + (v.verified_at || '') + '</span>'
                + '</div>'
                + '<div class="small tm-muted mb-1">by <strong>' + (v.verified_by || 'Staff') + '</strong> (Staff)</div>'
                + '<div class="rounded-3 p-2" style="background:#fceaea;font-size:.8rem;"><strong>Reason:</strong> ' + v.rejection_reason + '</div>'
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
