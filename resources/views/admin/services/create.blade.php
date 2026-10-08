@extends('layouts.app')

@section('title', 'Add Service — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    .tm-blue-switch:checked {
        background-color: #0a4fc4;
        border-color: #0a4fc4;
    }
    .tm-section-card { overflow: hidden; }
    .tm-section-title {
        background: #101b3d;
        color: #fff;
        padding: .85rem 1.25rem;
        font-weight: 700;
        font-size: .95rem;
    }
    .tm-field-icon {
        position: relative;
    }
    .tm-field-icon svg {
        position: absolute;
        left: .8rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa1b0;
        pointer-events: none;
    }
    .tm-field-icon input {
        padding-left: 2.3rem;
    }
    .tm-segmented {
        display: inline-flex;
        border: 1px solid var(--tm-surface-border);
        border-radius: .5rem;
        overflow: hidden;
    }
    .tm-segmented button {
        border: 0;
        background: #fff;
        padding: .55rem 1rem;
        font-size: .85rem;
        font-weight: 600;
        color: var(--tm-muted);
    }
    .tm-segmented button.active {
        background: #101b3d;
        color: #fff;
    }
    .tm-segmented-sm button {
        padding: .3rem .7rem;
        font-size: .78rem;
    }
    .tm-doc-row {
        border: 1px solid var(--tm-surface-border);
        border-radius: .6rem;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .6rem;
    }
    .tm-doc-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .tm-doc-name-input {
        border: 0;
        background: transparent;
        font-weight: 700;
        padding: 0;
        font-size: .82rem;
        width: 100%;
    }
    .tm-doc-name-input:focus {
        outline: none;
        box-shadow: none;
    }
    .tm-doc-sub-input {
        border: 0;
        background: transparent;
        padding: 0;
        font-size: .7rem;
        color: var(--tm-muted);
    }
    .tm-doc-sub-input:focus {
        outline: none;
        box-shadow: none;
        color: var(--tm-text);
    }
    .tm-doc-drag {
        cursor: grab;
        color: #c3c8d3;
        flex-shrink: 0;
    }
    .tm-remove-doc {
        border: 0;
        background: #fbe5ea;
        color: #c0392b;
        width: 28px;
        height: 28px;
        border-radius: .4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .tm-side-card-header {
        padding: .85rem 1.25rem;
        font-weight: 700;
        color: #fff;
    }
</style>
@endpush

@section('content')
@php
    $old = fn (string $key, $default = null) => old($key, $default);
    $oldDocuments = old('documents', [
        ['name' => 'PAN Card', 'instructions' => '', 'allowed_formats' => 'PDF, JPG, PNG', 'max_file_size_mb' => 5, 'mandatory' => '1'],
        ['name' => 'Aadhaar Card', 'instructions' => '', 'allowed_formats' => 'PDF, JPG, PNG', 'max_file_size_mb' => 5, 'mandatory' => '1'],
    ]);
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Services', 'url' => route('admin.services.index')], ['label' => 'Add Master Service']]" />

<x-page-header title="Add Master Service" subtitle="Set the fee and documents for a new service">
    <x-slot:actions><a href="{{ route('admin.services.index') }}" class="btn btn-tm-primary">Back to services</a></x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('admin.services.store') }}" id="serviceForm">
    @csrf

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title">1. Service details</div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="tm-field-label d-block">Service name <span class="text-danger">*</span></label>
                        <div class="tm-field-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"></path><circle cx="7" cy="7" r="1.4"></circle></svg>
                            <input type="text" name="name" value="{{ $old('name') }}" class="form-control tm-field @error('name') is-invalid @enderror" placeholder="e.g. GST Return Filing">
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Description</label>
                        <textarea name="description" id="descriptionField" maxlength="500" class="form-control tm-field @error('description') is-invalid @enderror" rows="3" placeholder="Short description shown to staff when they pick this service">{{ $old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input flex-shrink-0 tm-blue-switch" type="checkbox" role="switch" id="serviceActive" name="is_active" value="1" style="width: 2rem; height: 1.1rem;" {{ $old('is_active', true) ? 'checked' : '' }}>
                            <label class="mb-0" for="serviceActive" style="font-size: .78rem;">Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title">2. Fee</div>
                <div class="p-4">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-4">
                            <label class="tm-field-label d-block">Default price (₹) <span class="text-danger">*</span></label>
                            <div class="tm-field-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3c4 0 4-5 0-5H6"></path></svg>
                                <input type="number" name="default_price" id="defaultPriceInput" value="{{ $old('default_price') }}" class="form-control tm-field js-price-input @error('default_price') is-invalid @enderror" placeholder="4000" min="0" step="0.01" inputmode="decimal">
                            </div>
                            @if ($errors->has('default_price'))
                                <div class="invalid-feedback d-block">{{ $errors->first('default_price') }}</div>
                            @else
                                <div class="invalid-feedback">Enter a valid amount</div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="tm-field-label d-block">GST rate <span class="text-danger">*</span></label>
                            <select name="gst_percent" id="gstPercentInput" class="form-select tm-field @error('gst_percent') is-invalid @enderror">
                                @foreach ([0, 5, 12, 18, 28] as $rate)
                                    <option value="{{ $rate }}" {{ (string) $old('gst_percent', 18) === (string) $rate ? 'selected' : '' }}>{{ $rate }}%</option>
                                @endforeach
                            </select>
                            @error('gst_percent')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="tm-field-label d-block">Price is</label>
                            <div class="tm-segmented tm-segmented-sm w-100">
                                <button type="button" class="flex-grow-1 js-price-mode {{ ! $old('price_includes_gst') ? 'active' : '' }}" data-value="0">GST excluded</button>
                                <button type="button" class="flex-grow-1 js-price-mode {{ $old('price_includes_gst') ? 'active' : '' }}" data-value="1">GST included</button>
                            </div>
                            <input type="hidden" name="price_includes_gst" id="priceIncludesGst" value="{{ $old('price_includes_gst') ? '1' : '0' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title d-flex align-items-center justify-content-between">
                    <span>3. Documents required</span>
                    <span class="small fw-normal" id="documentsSummary" style="color: rgba(255,255,255,.75);"></span>
                </div>
                <div class="p-4">
                    <div id="documentRows">
                        @foreach ($oldDocuments as $i => $doc)
                            <div class="tm-doc-row js-document-row">
                                <span class="tm-doc-drag">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="5" r="1.3"></circle><circle cx="16" cy="5" r="1.3"></circle><circle cx="8" cy="12" r="1.3"></circle><circle cx="16" cy="12" r="1.3"></circle><circle cx="8" cy="19" r="1.3"></circle><circle cx="16" cy="19" r="1.3"></circle></svg>
                                </span>
                                <span class="tm-doc-icon" style="background: #e0edff; color: #2f5fbe;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                </span>
                                <div class="flex-grow-1">
                                    <input type="text" class="tm-doc-name-input d-block" name="documents[{{ $i }}][name]" value="{{ $doc['name'] ?? '' }}" placeholder="Document name">
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="text" class="tm-doc-sub-input js-doc-formats" name="documents[{{ $i }}][allowed_formats]" value="{{ $doc['allowed_formats'] ?? '' }}" placeholder="PDF, JPG, PNG" style="width: 140px;">
                                        <span class="tm-muted" style="font-size: .78rem;">&middot;</span>
                                        <input type="number" class="tm-doc-sub-input js-doc-size" name="documents[{{ $i }}][max_file_size_mb]" value="{{ $doc['max_file_size_mb'] ?? '' }}" min="0.1" step="0.1" placeholder="5" style="width: 40px;">
                                        <span class="tm-muted" style="font-size: .78rem;">MB</span>
                                    </div>
                                    <input type="text" class="tm-doc-sub-input d-block mt-1" name="documents[{{ $i }}][instructions]" value="{{ $doc['instructions'] ?? '' }}" placeholder="Instructions for this document (optional)">
                                </div>
                                <div class="tm-segmented tm-segmented-sm js-doc-mandatory-toggle flex-shrink-0">
                                    <button type="button" class="js-doc-mandatory {{ ! empty($doc['mandatory']) ? 'active' : '' }}" data-value="1">Mandatory</button>
                                    <button type="button" class="js-doc-optional {{ empty($doc['mandatory']) ? 'active' : '' }}" data-value="0">Optional</button>
                                </div>
                                <input type="hidden" name="documents[{{ $i }}][mandatory]" class="js-doc-mandatory-value" value="{{ ! empty($doc['mandatory']) ? '1' : '' }}">
                                <button type="button" class="tm-remove-doc js-remove-document-row" title="Remove">&times;</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-link text-decoration-none fw-semibold p-0 d-flex align-items-center gap-2" id="addDocumentRow" style="color: var(--tm-accent); font-size: .85rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add document
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-tm-primary">Save service</button>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="tm-card p-0 mb-3" style="overflow: hidden;">
                <div class="tm-side-card-header" style="background: #1f6b30;">Fee preview</div>
                <div class="p-3" style="font-size: .78rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="tm-muted">Default price <span id="previewPriceMode">(GST excluded)</span></span>
                        <span class="fw-semibold" id="previewDefaultPrice">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="tm-muted" id="previewGstLabel">GST (18%)</span>
                        <span class="fw-semibold" id="previewGstAmount">₹0.00</span>
                    </div>
                    <div class="rounded-3 p-3 d-flex justify-content-between align-items-center" style="background: #e5f5e0;">
                        <div>
                            <div class="fw-bold" style="color: #1f6b30;">Client pays</div>
                            <div style="color: #1f6b30; font-size: .72rem;">including GST</div>
                        </div>
                        <div class="fw-bold mb-0" style="color: #1f6b30; font-size: 1.1rem;" id="previewTotal">₹0</div>
                    </div>
                </div>
            </div>

            <div class="tm-card p-0" style="overflow: hidden;">
                <div class="tm-section-title d-flex align-items-center justify-content-between">Where this is used</div>
                <ul class="list-unstyled p-3 mb-0" style="font-size: .82rem;">
                    <li class="d-flex gap-2 mb-3">
                        <span class="rounded-circle d-inline-block flex-shrink-0 mt-1" style="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong class="d-block">New enquiry</strong><span class="tm-muted">Staff pick this service and its price is filled in</span></span>
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <span class="rounded-circle d-inline-block flex-shrink-0 mt-1" style="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong class="d-block">Client portal</strong><span class="tm-muted">The client sees this document checklist to upload</span></span>
                    </li>
                    <li class="d-flex gap-2 mb-0">
                        <span class="rounded-circle d-inline-block flex-shrink-0 mt-1" style="width:6px;height:6px;background:#0a4fc4;"></span>
                        <span><strong class="d-block">Existing tickets</strong><span class="tm-muted">Keep their own document list; changes apply to new tickets</span></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</form>

<template id="documentRowTemplate">
    <div class="tm-doc-row js-document-row">
        <span class="tm-doc-drag">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="5" r="1.3"></circle><circle cx="16" cy="5" r="1.3"></circle><circle cx="8" cy="12" r="1.3"></circle><circle cx="16" cy="12" r="1.3"></circle><circle cx="8" cy="19" r="1.3"></circle><circle cx="16" cy="19" r="1.3"></circle></svg>
        </span>
        <span class="tm-doc-icon" style="background: #e0edff; color: #2f5fbe;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
        </span>
        <div class="flex-grow-1">
            <input type="text" class="tm-doc-name-input d-block" name="documents[__INDEX__][name]" placeholder="Document name">
            <div class="d-flex align-items-center gap-1">
                <input type="text" class="tm-doc-sub-input js-doc-formats" name="documents[__INDEX__][allowed_formats]" placeholder="PDF, JPG, PNG" style="width: 140px;">
                <span class="tm-muted" style="font-size: .78rem;">&middot;</span>
                <input type="number" class="tm-doc-sub-input js-doc-size" name="documents[__INDEX__][max_file_size_mb]" min="0.1" step="0.1" placeholder="5" style="width: 40px;">
                <span class="tm-muted" style="font-size: .78rem;">MB</span>
            </div>
            <input type="text" class="tm-doc-sub-input d-block mt-1" name="documents[__INDEX__][instructions]" placeholder="Instructions for this document (optional)">
        </div>
        <div class="tm-segmented tm-segmented-sm js-doc-mandatory-toggle flex-shrink-0">
            <button type="button" class="js-doc-mandatory" data-value="1">Mandatory</button>
            <button type="button" class="js-doc-optional active" data-value="0">Optional</button>
        </div>
        <input type="hidden" name="documents[__INDEX__][mandatory]" class="js-doc-mandatory-value" value="">
        <button type="button" class="tm-remove-doc js-remove-document-row" title="Remove">&times;</button>
    </div>
</template>

@push('scripts')
<script>
    (function () {
        var rowsBody = document.getElementById('documentRows');
        var addBtn = document.getElementById('addDocumentRow');
        var template = document.getElementById('documentRowTemplate');
        var rowIndex = rowsBody.querySelectorAll('.js-document-row').length;

        function updateDocumentsSummary() {
            var rows = rowsBody.querySelectorAll('.js-document-row');
            var mandatory = 0;
            var optional = 0;

            rows.forEach(function (row) {
                if (row.querySelector('.js-doc-mandatory-value').value === '1') {
                    mandatory++;
                } else {
                    optional++;
                }
            });

            document.getElementById('documentsSummary').textContent = mandatory + ' mandatory · ' + optional + ' optional';
        }

        function wireMandatoryToggle(row) {
            var hidden = row.querySelector('.js-doc-mandatory-value');
            var mandatoryBtn = row.querySelector('.js-doc-mandatory');
            var optionalBtn = row.querySelector('.js-doc-optional');

            mandatoryBtn.addEventListener('click', function () {
                hidden.value = '1';
                mandatoryBtn.classList.add('active');
                optionalBtn.classList.remove('active');
                updateDocumentsSummary();
            });

            optionalBtn.addEventListener('click', function () {
                hidden.value = '';
                optionalBtn.classList.add('active');
                mandatoryBtn.classList.remove('active');
                updateDocumentsSummary();
            });
        }

        rowsBody.querySelectorAll('.js-document-row').forEach(wireMandatoryToggle);
        updateDocumentsSummary();

        addBtn.addEventListener('click', function () {
            var clone = template.content.cloneNode(true);
            clone.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace('__INDEX__', rowIndex);
            });
            rowIndex++;
            rowsBody.appendChild(clone);
            wireMandatoryToggle(rowsBody.lastElementChild);
            updateDocumentsSummary();
        });

        rowsBody.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-remove-document-row');
            if (btn) {
                btn.closest('.js-document-row').remove();
                updateDocumentsSummary();
            }
        });

        // Price-is segmented toggle
        var priceIncludesGst = document.getElementById('priceIncludesGst');
        document.querySelectorAll('.js-price-mode').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.js-price-mode').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                priceIncludesGst.value = btn.getAttribute('data-value');
                updateFeePreview();
            });
        });

        // Description character counter
        var descriptionField = document.getElementById('descriptionField');
        var descriptionCount = document.getElementById('descriptionCount');
        if (descriptionCount) {
            function updateDescriptionCount() { descriptionCount.textContent = descriptionField.value.length; }
            descriptionField.addEventListener('input', updateDescriptionCount);
            updateDescriptionCount();
        }

        // Fee preview
        var defaultPriceInput = document.getElementById('defaultPriceInput');
        var gstPercentInput = document.getElementById('gstPercentInput');

        function formatRupees(value) {
            return '₹' + value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateFeePreview() {
            var price = parseFloat(defaultPriceInput.value) || 0;
            var gstPercent = parseFloat(gstPercentInput.value) || 0;
            var includesGst = priceIncludesGst.value === '1';

            var gstAmount = includesGst ? (price - (price / (1 + gstPercent / 100))) : (price * gstPercent / 100);
            var total = includesGst ? price : (price + gstAmount);

            document.getElementById('previewGstLabel').textContent = 'GST (' + gstPercent + '%)';
            document.getElementById('previewPriceMode').textContent = includesGst ? '(GST included)' : '(GST excluded)';
            document.getElementById('previewDefaultPrice').textContent = formatRupees(price);
            document.getElementById('previewGstAmount').textContent = formatRupees(gstAmount);
            document.getElementById('previewTotal').textContent = '₹' + Math.round(total).toLocaleString('en-IN');
        }

        defaultPriceInput.addEventListener('input', updateFeePreview);
        gstPercentInput.addEventListener('change', updateFeePreview);
        updateFeePreview();
    })();
</script>
@endpush
@endsection
