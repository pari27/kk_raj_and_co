@extends('layouts.customer')

@section('title', $ticket->service->name . ' — ' . config('app.firm_name'))

@section('content')
@php
    $money = fn ($amount) => '₹'.number_format((float) $amount);
    $closed = $ticket->status->isClosed();
    $step = $stage['step'];
    $stepTone = $step === 1 ? '' : ($step === 2 ? 'blue' : ($step >= 4 ? 'green' : ''));
    $niceFormats = function (?string $formats) {
        $list = array_values(array_filter(array_map('trim', explode(',', (string) $formats))));

        return count($list) > 1 ? implode(', ', array_slice($list, 0, -1)).' or '.end($list) : ($list[0] ?? '');
    };
    $when = fn ($date) => $date->isToday() ? 'Today, '.$date->format('g:i A') : ($date->isYesterday() ? 'Yesterday, '.$date->format('g:i A') : $date->format('j M Y'));
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('customer.dashboard')], ['label' => 'My services', 'url' => route('customer.tickets.index')], ['label' => $ticket->service->name]]" />

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="tm-serif fw-bold mb-0" style="font-size: 1.15rem;">{{ $ticket->service->name }}</h1>
            <span class="ppill {{ $stage['tone'] }} d-none d-md-inline-flex">{{ $stage['label'] }}</span>
        </div>
        <p class="tm-muted mb-0" style="font-size: .8rem;">{{ $ticket->number }} &middot; Started {{ $ticket->created_at->format('j M Y') }}</p>
    </div>
    <a href="{{ route('customer.tickets.index') }}" class="pbtn-navy d-none d-md-inline-flex"><x-portal-icon name="arrow-left" :size="15" /> My services</a>
</div>

<div class="p-card">
    <div class="p-card-body">
        {{-- Computer: five steps joined by a line --}}
        <div class="p-stepper">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; $state = $n < $step ? 'done' : ($n === $step ? 'current' : ''); @endphp
                <div class="p-step {{ $state }} {{ $state === 'current' ? $stepTone : '' }}">
                    <span class="dot">@if ($state === 'done')<x-portal-icon name="check" :size="15" />@endif</span>
                    {{ $label }}
                </div>
                @if (! $loop->last)<div class="p-line {{ $n < $step ? 'done' : '' }}"></div>@endif
            @endforeach
        </div>

        {{-- Phone: a pill, a bar and the path --}}
        <div class="p-stepbar">
            <div class="d-flex align-items-center justify-content-between">
                <span class="ppill {{ $stage['tone'] }}">{{ $stage['label'] }}</span>
                <span class="eyebrow tm-muted" style="font-size: .7rem; letter-spacing: .08em; font-weight: 700;">STEP {{ $step }} OF {{ count($steps) }}</span>
            </div>
            <div class="segs">
                @foreach ($steps as $index => $label)<span class="{{ $index < $step ? 'on' : '' }}"></span>@endforeach
            </div>
            <div class="trail">{{ implode(' → ', $steps) }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="p-card">
            <div class="p-card-head navy">
                <span>Documents</span>
                @if ($documentRows->isNotEmpty())
                    <span class="meta">{{ $documentsDone }} of {{ $documentRows->count() }} verified</span>
                @endif
            </div>

            @if ($closed)
                <div class="p-note">This service is complete, so no more documents can be sent.</div>
            @endif

            @forelse ($documentRows as $row)
                @php
                    $document = $row['serviceDocument'];
                    $current = $row['current'];
                    $limits = $document->isText() ? 'Type the details' : trim($niceFormats($document->allowed_formats).($document->formattedMaxSize() ? ' · up to '.$document->formattedMaxSize() : ''));
                @endphp
                <div class="p-doc">
                    <span class="ico"><x-portal-icon name="file" :size="19" /></span>

                    <div>
                        <div class="nm">{{ $document->name }} <span class="p-req {{ $document->is_mandatory ? 'mandatory' : '' }}">{{ $document->is_mandatory ? 'Mandatory' : 'Optional' }}</span></div>
                        @if ($current && $current->status === 'Rejected' && $current->rejection_reason)
                            <div class="reason">Reason: {{ $current->rejection_reason }}</div>
                        @endif
                        <div class="sub">
                            @if ($current)
                                Uploaded by you on {{ $current->created_at->format('j M Y') }}
                                @if ($current->text_value !== null)&middot; {{ $current->text_value }}
                                @elseif ($current->file_path)&middot; <a href="{{ route('customer.documents.view', [$ticket, $current]) }}" class="file" target="_blank" rel="noopener">View</a>
                                @endif
                            @else
                                Not uploaded yet
                            @endif
                        </div>
                        <div class="sub" style="font-size: .66rem;">{{ $limits }}</div>
                    </div>

                    <div class="acts">
                        @if (! $current)
                            <span class="ppill amber nodot">Not uploaded</span>
                        @elseif ($current->status === 'Verified')
                            <span class="ppill lightgreen nodot">Verified</span>
                        @elseif ($current->status === 'Rejected')
                            <span class="ppill red nodot">Rejected</span>
                        @else
                            <span class="ppill blue nodot">Being checked</span>
                        @endif

                        @if ($row['needsUpload'])
                            <button type="button" class="pbtn-green js-open-upload"
                                data-bs-toggle="modal" data-bs-target="#uploadSheet"
                                data-id="{{ $document->id }}"
                                data-name="{{ $document->name }}"
                                data-instructions="{{ $document->instructions }}"
                                data-text="{{ $document->isText() ? '1' : '' }}"
                                data-limits="{{ $limits }}"
                                data-images="{{ str_contains(strtolower((string) $document->allowed_formats), 'jpg') || str_contains(strtolower((string) $document->allowed_formats), 'png') ? '1' : '' }}"
                            >{{ $current ? 'Re-upload' : 'Upload' }}</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-card-body tm-muted" style="font-size: .85rem;">No documents are needed for this service.</div>
            @endforelse
        </div>

        <div class="p-card">
            <div class="p-card-head navy"><span>Updates</span></div>
            <div class="p-tl">
                @forelse ($timeline as $entry)
                    <div class="p-tl-item">
                        <span class="dot {{ $entry['tone'] }}"></span>
                        <div class="tt">{{ $entry['title'] }}</div>
                        <div class="tm">{{ $when($entry['when']) }}</div>
                    </div>
                @empty
                    <div class="tm-muted py-3" style="font-size: .85rem;">Nothing to show yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="p-card">
            <div class="p-card-head green"><span>Fee</span></div>
            <div class="p-card-body">
                <div class="p-fee-row"><span>Price</span><span>{{ $money($ticket->price) }}</span></div>
                <div class="p-fee-row"><span>GST ({{ rtrim(rtrim(number_format((float) $ticket->gst_percent, 2), '0'), '.') }}%)</span><span>{{ $money($ticket->gst_amount) }}</span></div>

                @if ($fee['balance'] > 0)
                    <div class="p-topay">
                        <div>
                            <div class="tt">To pay</div>
                            <div class="sb">{{ $closed ? 'due now' : 'after the work is completed' }}</div>
                        </div>
                        <div class="amt">{{ $money($ticket->total) }}</div>
                    </div>
                    <a href="{{ route('customer.payments.index') }}" class="d-inline-block mt-3 fw-bold text-decoration-none" style="color: var(--p-green); font-size: .9rem;">How to pay →</a>
                @else
                    <div class="p-topay paid">
                        <div><div class="tt">Paid</div><div class="sb">Thank you</div></div>
                        <div class="amt">{{ $money($ticket->total) }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-head navy"><span>Your final documents</span></div>
            <div class="p-card-body d-flex gap-3 align-items-start">
                <span class="p-doc" style="padding: 0; border: 0;"><span class="ico" style="background: #dff3ea; color: #0f766e;"><x-portal-icon name="download" :size="19" /></span></span>
                <div class="tm-muted" style="font-size: .85rem;">Your final documents will appear here once the work is completed.</div>
            </div>
        </div>
    </div>
</div>

{{-- Upload sheet: one for every document on this page --}}
<div class="modal fade p-sheet" id="uploadSheet" tabindex="-1" aria-labelledby="uploadSheetTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('customer.documents.store', $ticket) }}" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <input type="hidden" name="service_document_id" id="uploadDocId">

            <div class="p-sheet-head">
                <div>
                    <h3 id="uploadSheetTitle">Upload document</h3>
                    <div class="sb"><span>{{ $ticket->service->name }}</span><span id="uploadHeadLimits"></span></div>
                </div>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"><x-portal-icon name="x" :size="16" /></button>
            </div>

            <div class="p-3">
                <div class="tm-muted mb-2" id="uploadInstructions" style="font-size: .85rem;"></div>

                <div id="uploadFileArea">
                    <div class="p-choice">
                        <label id="cameraChoice">
                            <span class="g"><x-portal-icon name="camera" :size="26" /></span>
                            Take a photo
                            <input type="file" id="cameraInput" accept="image/*" capture="environment">
                        </label>
                        <label class="dashed">
                            <span class="b"><x-portal-icon name="upload" :size="26" /></span>
                            Choose a file
                            <input type="file" id="fileInput">
                        </label>
                    </div>

                    <div class="p-picked" id="pickedFile">
                        <span class="ico"><x-portal-icon name="file" :size="18" /></span>
                        <div class="meta">
                            <div class="row1"><span class="nm" id="pickedName"></span><span class="sz" id="pickedSize"></span></div>
                            <div class="progress-line"></div>
                        </div>
                    </div>
                    <div class="p-sheet-hint" id="uploadLimits"></div>
                </div>

                <div id="uploadTextArea" class="d-none mb-3">
                    <label class="small fw-semibold mb-1" for="uploadText">Details</label>
                    <textarea id="uploadText" class="form-control tm-field" rows="3" maxlength="2000" placeholder="Type the details here"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="pbtn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="pbtn-green lg flex-grow-1" id="uploadSubmit" disabled>Upload document</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var cameraInput = document.getElementById('cameraInput');
        var fileInput = document.getElementById('fileInput');
        var textArea = document.getElementById('uploadText');
        var submit = document.getElementById('uploadSubmit');
        var picked = document.getElementById('pickedFile');

        function reset(isText) {
            cameraInput.value = '';
            fileInput.value = '';
            cameraInput.removeAttribute('name');
            fileInput.removeAttribute('name');
            textArea.value = '';
            textArea.removeAttribute('name');
            picked.classList.remove('show');
            submit.disabled = true;

            if (isText) {
                textArea.setAttribute('name', 'text_value');
            }
        }

        function pick(input, other) {
            if (!input.files.length) {
                return;
            }
            var file = input.files[0];

            other.value = '';
            other.removeAttribute('name');
            input.setAttribute('name', 'file');

            document.getElementById('pickedName').textContent = file.name;
            document.getElementById('pickedSize').textContent = file.size >= 1048576
                ? (file.size / 1048576).toFixed(1) + ' MB'
                : Math.max(1, Math.round(file.size / 1024)) + ' KB';
            picked.classList.add('show');
            submit.disabled = false;
        }

        cameraInput.addEventListener('change', function () { pick(cameraInput, fileInput); });
        fileInput.addEventListener('change', function () { pick(fileInput, cameraInput); });
        textArea.addEventListener('input', function () { submit.disabled = textArea.value.trim() === ''; });

        document.querySelectorAll('.js-open-upload').forEach(function (button) {
            button.addEventListener('click', function () {
                var isText = button.dataset.text === '1';

                document.getElementById('uploadDocId').value = button.dataset.id;
                document.getElementById('uploadSheetTitle').textContent = (isText ? 'Enter ' : 'Upload ') + button.dataset.name;
                document.getElementById('uploadHeadLimits').textContent = button.dataset.limits ? ' · ' + button.dataset.limits : '';
                document.getElementById('uploadInstructions').textContent = button.dataset.instructions || '';
                document.getElementById('uploadLimits').textContent = button.dataset.limits ? button.dataset.limits + '.' : '';

                document.getElementById('uploadFileArea').classList.toggle('d-none', isText);
                document.getElementById('uploadTextArea').classList.toggle('d-none', !isText);
                document.getElementById('cameraChoice').classList.toggle('d-none', button.dataset.images !== '1');

                reset(isText);
            });
        });
    })();
</script>
@endpush
