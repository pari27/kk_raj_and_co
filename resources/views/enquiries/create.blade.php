@extends('layouts.app')

@section('title', 'New Enquiry — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    .tm-client-result {
        border: 1px solid var(--tm-surface-border);
        border-radius: .6rem;
        padding: .75rem 1rem;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .tm-client-result:hover { background: #f8f9fb; }
    .tm-client-result.is-selected {
        border-color: var(--tm-accent);
        background: var(--tm-accent-soft);
    }
    .tm-client-avatar-sm {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #101b3d;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .78rem;
        flex-shrink: 0;
    }
    .tm-enquiry-service-row {
        border: 1px solid var(--tm-surface-border);
        border-radius: .6rem;
        padding: .85rem 1rem;
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        margin-bottom: .6rem;
    }
    .tm-enquiry-service-row.is-searching {
        border-color: var(--tm-accent);
    }
    .tm-enquiry-service-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        background: #e0edff;
        color: #2f5fbe;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1.4rem;
    }
    .tm-service-row-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--tm-muted);
        text-transform: uppercase;
        margin-bottom: .3rem;
        display: block;
    }
    .tm-service-search-wrap {
        position: relative;
    }
    .tm-service-dropdown {
        position: absolute;
        top: calc(100% + .35rem);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid var(--tm-surface-border);
        border-radius: .6rem;
        box-shadow: 0 10px 30px rgba(16,27,61,.14);
        z-index: 20;
        max-height: 320px;
        overflow-y: auto;
        padding: .5rem 0;
    }
    .tm-service-dropdown-header {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .03em;
        color: var(--tm-muted);
        text-transform: uppercase;
        padding: .3rem 1rem .5rem;
    }
    .tm-service-option {
        padding: .5rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .tm-service-option:hover, .tm-service-option.is-active {
        background: #eef4ff;
    }
    .tm-service-option-name {
        font-weight: 700;
        font-size: .85rem;
        color: var(--tm-text);
    }
    .tm-service-option-meta {
        font-size: .74rem;
        color: var(--tm-muted);
        margin-top: .1rem;
    }
    .tm-service-option-price {
        font-weight: 700;
        font-size: .85rem;
        text-align: right;
        white-space: nowrap;
    }
    .tm-service-option-gst {
        font-size: .7rem;
        color: var(--tm-muted);
        font-weight: 400;
        text-align: right;
    }
    .tm-service-dropdown-footer {
        font-size: .7rem;
        color: var(--tm-muted);
        text-align: center;
        padding: .5rem 1rem 0;
    }
    .tm-service-dropdown-empty {
        font-size: .8rem;
        color: var(--tm-muted);
        padding: .5rem 1rem;
    }

    .tm-add-service-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        width: 100%;
        border: 1.5px dashed #bcd6ff;
        background: #f3f8ff;
        color: var(--tm-accent);
        font-weight: 600;
        font-size: .85rem;
        border-radius: .6rem;
        padding: .7rem;
    }
    .tm-add-service-btn:hover {
        background: #e9f2ff;
    }
    .tm-service-match {
        background: #fff3a3;
        padding: 0;
    }
    .tm-whats-next {
        background: #eaf1fd;
        border: 1px solid #cfe0fb;
        border-radius: .6rem;
        padding: 1rem;
    }
    .tm-lock-note {
        color: var(--tm-muted);
        font-size: .78rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
</style>
@endpush

@section('content')
@php
    $servicesData = $services->map(fn ($service) => [
        'id' => $service->id,
        'name' => $service->name,
        'price' => (float) $service->default_price,
        'gst_percent' => (float) $service->gst_percent,
        'price_includes_gst' => (bool) $service->price_includes_gst,
        'documents_count' => $service->documents_count,
        'document_names' => $service->documents->pluck('name')->values(),
    ])->values();

    $avatarPalette = [
        ['bg' => '#e0edff', 'text' => '#2f5fbe'],
        ['bg' => '#e5f5e0', 'text' => '#2f8f3e'],
        ['bg' => '#ece5fb', 'text' => '#6d5bd0'],
        ['bg' => '#fbe5ea', 'text' => '#b91c4a'],
        ['bg' => '#fdecd2', 'text' => '#b9650a'],
    ];

    $employeesData = $employees->values()->map(function ($employee, $i) use ($avatarPalette) {
        $words = preg_split('/\s+/', trim($employee->name));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1).substr($words[1] ?? '', 0, 1));

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'role' => $employee->role->label(),
            'initials' => $initials,
            'color' => $avatarPalette[$i % count($avatarPalette)],
        ];
    })->values();

    $currentUserId = auth()->id();
    $currentUserIsEmployee = auth()->user()->isEmployee();
@endphp

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Enquiries', 'url' => route('enquiries.index')], ['label' => 'New enquiry']]" />
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-gold">
    <div>
        <h1 class="tm-serif fw-bold mb-1" style="font-size: 1.15rem;">New enquiry</h1>
        <p class="tm-muted mb-0" style="font-size: .8rem;">Add a client and the services they need</p>
    </div>
</div>

<form method="POST" action="{{ route('enquiries.store') }}" id="enquiryForm">
    @csrf
    <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id') }}">

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title d-flex align-items-center justify-content-between">
                    <span>1. Client</span>
                    <span class="small fw-normal" style="color: rgba(255,255,255,.75);">Start with the mobile number</span>
                </div>
                <div class="p-4">
                    @error('customer_id')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror
                    @error('customer_phone')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror
                    @error('customer_name')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror
                    @error('customer_email')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror

                    <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id') }}">

                    <label class="tm-field-label d-block">Mobile number <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <span class="d-flex align-items-center px-3" style="background: #f3f4f7; border: 1px solid var(--tm-surface-border); border-radius: .5rem; font-size: .85rem; color: var(--tm-muted);">+91</span>
                        <input type="text" name="customer_phone" id="clientPhoneInput" inputmode="numeric" maxlength="10" class="form-control tm-field" placeholder="98765 43210" value="{{ old('customer_phone') }}">
                    </div>

                    <div id="clientLookupBanner" class="rounded-3 p-3 mt-3 d-none"></div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Full name / business name <span class="text-danger">*</span></label>
                            <div class="tm-field-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <input type="text" name="customer_name" id="clientNameInput" class="form-control tm-field" placeholder="Full name" value="{{ old('customer_name') }}">
                            </div>
                            <div class="tm-muted d-none client-record-caption" id="clientNameCaption">From the client record</div>
                        </div>
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Email <span class="tm-muted fw-normal">(optional)</span></label>
                            <div class="tm-field-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <input type="email" name="customer_email" id="clientEmailInput" class="form-control tm-field" placeholder="name@example.com" value="{{ old('customer_email') }}">
                            </div>
                            <div class="tm-muted d-none client-record-caption" id="clientEmailCaption">From the client record</div>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="customer_email_notifications" value="1" id="clientNotifyCheckbox">
                        <label class="form-check-label fw-semibold" for="clientNotifyCheckbox" style="font-size: .85rem;">Send email notifications to this client</label>
                        <div style="font-size: .72rem;" id="clientNotifyCaption"></div>
                    </div>
                </div>
            </div>

            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title d-flex align-items-center justify-content-between">
                    <span>2. Services</span>
                    <span class="small fw-normal" style="color: rgba(255,255,255,.75);">One ticket is created for each service</span>
                </div>
                <div class="p-4">
                    @error('services')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror

                    <div id="serviceRows"></div>

                    <button type="button" class="tm-add-service-btn" id="addServiceBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add service
                    </button>
                    <hr class="my-3">
                    <div class="tm-lock-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Prices come from the Services master. Only Admin can change them.
                    </div>
                </div>
            </div>

            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title">3. Notes (optional)</div>
                <div class="p-4">
                    <textarea name="notes" rows="3" class="form-control tm-field" placeholder="Anything the team should know about this enquiry">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('enquiries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-tm-primary" id="submitEnquiryBtn">Create <span id="submitTicketCount">0</span> tickets</button>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="tm-card p-0 mb-3" style="overflow: hidden;">
                <div class="tm-side-card-header" style="background: #1f6b30;">Summary</div>
                <div class="p-3" style="font-size: .85rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="tm-muted">Client</span>
                        <span class="fw-semibold" id="summaryClient">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="tm-muted">Services</span>
                        <span class="fw-semibold" id="summaryServiceCount">0</span>
                    </div>
                    <div id="summaryServiceLines" class="mb-2"></div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="tm-muted">Discount</span>
                        @if ($canManagePricing)
                            <input type="number" name="discount" id="discountInput" class="form-control form-control-sm tm-field text-end" style="width: 110px;" value="{{ old('discount', 0) }}" min="0" step="0.01">
                        @else
                            <span class="tm-lock-note">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Admin only
                            </span>
                        @endif
                    </div>

                    <div class="rounded-3 p-3 mt-2" style="background: #e5f5e0;">
                        <div class="fw-bold" style="color: #1f6b30;">Total</div>
                        <div style="color: #1f6b30; font-size: .72rem;" class="mb-1">including GST</div>
                        <div class="fw-bold" style="color: #1f6b30; font-size: 1.2rem;" id="summaryTotal">₹0</div>
                    </div>
                </div>
            </div>

            <div class="tm-whats-next">
                <div class="fw-bold mb-2" style="color: #0a4fc4;">What happens next</div>
                <div class="d-flex gap-2 mb-2" style="font-size: .8rem;">
                    <span class="text-success">&check;</span>
                    <span id="whatsNextTickets">Tickets will be created{{ auth()->user()->isEmployee() ? ' and assigned to you' : '' }}</span>
                </div>
                <div class="d-flex gap-2" style="font-size: .8rem;">
                    <span class="text-primary">&#9993;</span>
                    <span id="whatsNextEmail">No email notifications for this client</span>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var services = @json($servicesData);
        var employees = @json($employeesData);
        var canManagePricing = @json($canManagePricing);
        var canAssignTickets = @json($canAssignTickets);
        var currentUserId = @json($currentUserId);
        var currentUserIsEmployee = @json($currentUserIsEmployee);
        var oldServices = @json(old('services', []));

        var servicesById = {};
        services.forEach(function (s) { servicesById[s.id] = s; });

        var employeesById = {};
        employees.forEach(function (e) { employeesById[e.id] = e; });

        function formatMoney(value) {
            return '₹' + (Math.round(value * 100) / 100).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        // --- Client tabs ---
        var clientPhoneInput = document.getElementById('clientPhoneInput');
        var clientLookupBanner = document.getElementById('clientLookupBanner');
        var clientNameInput = document.getElementById('clientNameInput');
        var clientEmailInput = document.getElementById('clientEmailInput');
        var clientNameCaption = document.getElementById('clientNameCaption');
        var clientEmailCaption = document.getElementById('clientEmailCaption');
        var clientNotifyCheckbox = document.getElementById('clientNotifyCheckbox');
        var clientNotifyCaption = document.getElementById('clientNotifyCaption');
        var whatsNextEmail = document.getElementById('whatsNextEmail');
        var customerIdInput = document.getElementById('customerIdInput');
        var lookupTimer = null;

        function updateNotifyState() {
            var hasEmail = clientEmailInput.value.trim().length > 0;
            clientNotifyCheckbox.disabled = ! hasEmail;
            if (! hasEmail) {
                clientNotifyCheckbox.checked = false;
                clientNotifyCaption.textContent = "Add an email to turn this on. Without an email the client can't log in to the portal, so staff upload documents for them.";
                clientNotifyCaption.style.color = '#8a5a16';
            } else {
                clientNotifyCaption.textContent = 'Portal link, document list, status and payment confirmation';
                clientNotifyCaption.style.color = '#1f6b30';
            }
            whatsNextEmail.textContent = clientNotifyCheckbox.checked
                ? 'This client is marked to receive email notifications'
                : 'No email notifications for this client';
        }

        function applyNewClientState() {
            customerIdInput.value = '';
            clientLookupBanner.style.background = '#eef4ff';
            clientLookupBanner.style.border = '1px solid #cfe0ff';
            clientLookupBanner.innerHTML = '<div class="d-flex align-items-start gap-2">'
                + '<span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;background:#2f5fbe;color:#fff;font-size:.9rem;line-height:1;">+</span>'
                + '<div><div class="fw-bold" style="font-size:.82rem;color:#15245c;">New client</div>'
                + '<div style="font-size:.76rem;color:#2f3a52;">This number isn’t registered yet. Fill in the name and a new client is created when you save.</div></div>'
                + '</div>';
            clientNameInput.disabled = false;
            clientEmailInput.disabled = false;
            clientNameCaption.classList.add('d-none');
            clientEmailCaption.classList.add('d-none');
            document.getElementById('summaryClient').textContent = '—';
            updateNotifyState();
        }

        function applyExistingClientState(customer) {
            customerIdInput.value = customer.id;
            clientLookupBanner.style.background = '#eaf7ec';
            clientLookupBanner.style.border = '1px solid #cdeccb';
            var initials = customer.name.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
            var historyText = customer.tickets_count === 1 ? '1 past ticket' : (customer.tickets_count + ' past tickets');
            clientLookupBanner.innerHTML = '<div class="d-flex align-items-start justify-content-between gap-2">'
                + '<div class="d-flex align-items-start gap-2">'
                + '<span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;background:#1f6b30;color:#fff;font-size:.68rem;font-weight:700;">' + initials + '</span>'
                + '<div><div class="fw-bold" style="font-size:.82rem;color:#1f6b30;">Existing client found</div>'
                + '<div style="font-size:.76rem;color:#2f3a52;">Client since ' + customer.since + ' &middot; ' + historyText + '</div></div>'
                + '</div>'
                + '<a href="{{ url('customers') }}/' + customer.id + '" target="_blank" class="fw-semibold text-decoration-none flex-shrink-0" style="font-size:.76rem;color:#1f6b30;">View client &rarr;</a>'
                + '</div>';
            clientNameInput.value = customer.name;
            clientEmailInput.value = customer.email || '';
            clientNameInput.disabled = true;
            clientNameCaption.classList.remove('d-none');
            if (customer.email) {
                clientEmailInput.disabled = true;
                clientEmailCaption.classList.remove('d-none');
            } else {
                clientEmailInput.disabled = false;
                clientEmailCaption.classList.add('d-none');
            }
            clientNotifyCheckbox.checked = !! customer.email_notifications_enabled;
            document.getElementById('summaryClient').textContent = customer.name;
            updateNotifyState();
        }

        function clearLookupState() {
            customerIdInput.value = '';
            clientLookupBanner.classList.add('d-none');
            clientLookupBanner.innerHTML = '';
            clientNameInput.disabled = false;
            clientEmailInput.disabled = false;
            clientNameCaption.classList.add('d-none');
            clientEmailCaption.classList.add('d-none');
            document.getElementById('summaryClient').textContent = '—';
        }

        function resetClientFields() {
            clientNameInput.value = '';
            clientEmailInput.value = '';
            clearLookupState();
            updateNotifyState();
        }

        clientPhoneInput.addEventListener('input', function () {
            clientPhoneInput.value = clientPhoneInput.value.replace(/\D/g, '').slice(0, 10);
            clearTimeout(lookupTimer);
            resetClientFields();
            var phone = clientPhoneInput.value;
            if (phone.length !== 10) {
                return;
            }
            lookupTimer = setTimeout(function () {
                fetch('{{ route('customers.lookup') }}?phone=' + phone)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.customer) {
                            applyExistingClientState(data.customer);
                        } else {
                            applyNewClientState();
                        }
                        clientLookupBanner.classList.remove('d-none');
                    });
            }, 400);
        });

        clientEmailInput.addEventListener('input', updateNotifyState);
        updateNotifyState();

        if (clientPhoneInput.value.length === 10) {
            clientPhoneInput.dispatchEvent(new Event('input'));
        }

        // --- Service rows ---
        var rowIndex = 0;
        var rowsContainer = document.getElementById('serviceRows');

        function updateSummary() {
            var rows = Array.prototype.filter.call(
                rowsContainer.querySelectorAll('.tm-enquiry-service-row'),
                function (row) { return !! row.dataset.serviceId; }
            );
            var subtotal = 0;
            var gstTotal = 0;
            var lines = document.getElementById('summaryServiceLines');
            lines.innerHTML = '';

            rows.forEach(function (row) {
                var serviceId = row.dataset.serviceId;
                var service = servicesById[serviceId];
                var priceInput = row.querySelector('.js-row-price');
                var price = priceInput ? (parseFloat(priceInput.value) || 0) : service.price;
                var gstAmount = service.price_includes_gst
                    ? (price - (price / (1 + service.gst_percent / 100)))
                    : (price * service.gst_percent / 100);
                var total = service.price_includes_gst ? price : (price + gstAmount);

                subtotal += price;
                gstTotal += gstAmount;

                var line = document.createElement('div');
                line.className = 'd-flex justify-content-between small mb-1';
                line.innerHTML = '<span class="tm-muted">' + service.name + '</span><span>' + formatMoney(total) + '</span>';
                lines.appendChild(line);
            });

            document.getElementById('summaryServiceCount').textContent = rows.length;
            document.getElementById('submitTicketCount').textContent = rows.length;

            var discountInput = document.getElementById('discountInput');
            var discount = (canManagePricing && discountInput) ? (parseFloat(discountInput.value) || 0) : 0;
            var total = Math.max(0, subtotal + gstTotal - discount);
            document.getElementById('summaryTotal').textContent = formatMoney(total);
        }

        function highlightMatch(name, query) {
            var idx = name.toLowerCase().indexOf(query.toLowerCase());
            if (! query || idx === -1) {
                return name;
            }
            return name.slice(0, idx)
                + '<mark class="tm-service-match">' + name.slice(idx, idx + query.length) + '</mark>'
                + name.slice(idx + query.length);
        }

        function createServiceRow(initialServiceId) {
            var row = document.createElement('div');
            row.className = 'tm-enquiry-service-row';

            function avatarHtml(emp) {
                if (! emp) {
                    return '<span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:20px;height:20px;background:#eceef2;color:#6b7280;font-size:.6rem;font-weight:700;">?</span>';
                }
                return '<span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:20px;height:20px;background:' + emp.color.bg + ';color:' + emp.color.text + ';font-size:.6rem;font-weight:700;">' + escapeHtml(emp.initials) + '</span>';
            }

            function escapeHtml(value) {
                var entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return String(value).replace(/[&<>"']/g, function (character) {
                    return entities[character];
                });
            }

            function assignmentOptionLabel(assignee) {
                return assignee.role === 'Employee' ? assignee.name : assignee.role + ' � ' + assignee.name;
            }

            function assignmentLabel(assignee) {
                return assignee.name;
            }

            var assignField = '';
            if (canAssignTickets && employees.length) {
                var optionsHtml = '<li><a class="dropdown-item d-flex align-items-center gap-2 js-assign-option" href="#" data-id="" style="font-size: .8rem;">'
                    + avatarHtml(null) + '<span>Unassigned</span></a></li>';
                employees.forEach(function (e) {
                    optionsHtml += '<li><a class="dropdown-item d-flex align-items-center gap-2 js-assign-option" href="#" data-id="' + e.id + '" style="font-size: .8rem;">'
                        + avatarHtml(e) + '<span>' + escapeHtml(assignmentOptionLabel(e)) + '</span></a></li>';
                });

                assignField = '<div class="flex-shrink-0" style="min-width: 150px;">'
                    + '<div class="tm-service-row-label">Assign to</div>'
                    + '<div class="dropdown">'
                    + '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 js-assign-toggle" data-bs-toggle="dropdown" style="font-size: .78rem; background: #fff;">'
                    + '<span class="js-assign-avatar">' + avatarHtml(null) + '</span>'
                    + '<span class="js-assign-name">Unassigned</span>'
                    + '<span class="badge rounded-pill text-bg-success js-assign-you d-none" style="font-size: .6rem;">You</span>'
                    + '</button>'
                    + '<ul class="dropdown-menu">' + optionsHtml + '</ul>'
                    + '</div>'
                    + '<input type="hidden" class="js-assign-value" value="">'
                    + '</div>';
            }

            row.innerHTML = '<span class="tm-enquiry-service-icon">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'
                + '</span>'
                + '<div class="flex-grow-1 tm-service-search-wrap">'
                + '<label class="tm-service-row-label">Service <span class="text-danger">*</span></label>'
                + '<div class="js-service-name-display fw-semibold d-none" style="font-size: .85rem;"></div>'
                + '<div class="tm-field-icon js-service-search-field">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>'
                + '<input type="text" class="form-control tm-field js-service-search" placeholder="Search and select a service" autocomplete="off">'
                + '</div>'
                + '<div class="tm-muted js-service-hint" style="font-size: .76rem; margin-top: .35rem;">Documents and price fill in after you pick a service</div>'
                + '<div class="js-service-docs d-none" style="font-size: .76rem; margin-top: .4rem;"></div>'
                + '<div class="tm-service-dropdown d-none"></div>'
                + '</div>'
                + assignField
                + '<div class="text-end flex-shrink-0" style="min-width: 110px;">'
                + '<div class="tm-service-row-label js-price-label">Price</div>'
                + '<div class="js-price-area tm-muted" style="font-size: .85rem;">&#8377; —</div>'
                + '</div>'
                + '<button type="button" class="tm-remove-doc js-remove-row" title="Remove" style="margin-top: 1.4rem;">&times;</button>';

            rowsContainer.appendChild(row);

            var searchInput = row.querySelector('.js-service-search');
            var dropdown = row.querySelector('.tm-service-dropdown');
            var activeIndex = -1;
            var currentResults = [];

            function closeDropdown() {
                dropdown.classList.add('d-none');
                dropdown.innerHTML = '';
                row.classList.remove('is-searching');
            }

            function renderResults(query) {
                var q = query.trim();
                currentResults = services.filter(function (s) {
                    return ! rowsContainer.querySelector('[data-service-id="' + s.id + '"]')
                        && s.name.toLowerCase().indexOf(q.toLowerCase()) !== -1;
                });
                activeIndex = currentResults.length ? 0 : -1;

                if (! q) {
                    closeDropdown();
                    return;
                }

                if (! currentResults.length) {
                    dropdown.innerHTML = '<div class="tm-service-dropdown-empty">No services match "' + q + '"</div>';
                    dropdown.classList.remove('d-none');
                    return;
                }

                var html = '<div class="tm-service-dropdown-header">' + currentResults.length
                    + (currentResults.length === 1 ? ' service matches "' : ' services match "') + q + '"</div>';
                currentResults.forEach(function (s, idx) {
                    var gstLabel = s.price_includes_gst ? 'GST incl.' : ('+' + s.gst_percent + '% GST');
                    var docsLabel = s.document_names.length === 1 ? '1 document' : (s.document_names.length + ' documents');
                    html += '<div class="tm-service-option' + (idx === activeIndex ? ' is-active' : '') + '" data-index="' + idx + '">'
                        + '<div><div class="tm-service-option-name">' + highlightMatch(s.name, q) + '</div>'
                        + '<div class="tm-service-option-meta">' + docsLabel + '</div></div>'
                        + '<div><div class="tm-service-option-price">' + formatMoney(s.price) + '</div>'
                        + '<div class="tm-service-option-gst">' + gstLabel + '</div></div>'
                        + '</div>';
                });
                html += '<div class="tm-service-dropdown-footer">&uarr;&darr; to move &middot; Enter to select &middot; Esc to close</div>';
                dropdown.innerHTML = html;
                dropdown.classList.remove('d-none');

                dropdown.querySelectorAll('.tm-service-option').forEach(function (el) {
                    el.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        selectService(currentResults[parseInt(el.dataset.index, 10)]);
                    });
                });
            }

            function highlightActive() {
                dropdown.querySelectorAll('.tm-service-option').forEach(function (el, idx) {
                    el.classList.toggle('is-active', idx === activeIndex);
                });
            }

            function selectService(service) {
                row.dataset.serviceId = service.id;
                var i = rowIndex++;

                row.querySelector('.js-service-search-field').style.display = 'none';
                var nameDisplay = row.querySelector('.js-service-name-display');
                nameDisplay.textContent = service.name;
                nameDisplay.classList.remove('d-none');
                closeDropdown();

                row.querySelector('.js-service-hint').classList.add('d-none');

                var docsWrap = row.querySelector('.js-service-docs');
                if (service.document_names.length) {
                    docsWrap.innerHTML = service.document_names.map(function (d) { return '<span class="tm-service-doc-chip">' + d + '</span>'; }).join('');
                } else {
                    docsWrap.innerHTML = '<span class="tm-muted">No documents required</span>';
                }
                docsWrap.classList.remove('d-none');

                var gstCaption = service.price_includes_gst ? 'GST included' : ('+' + service.gst_percent + '% GST');
                row.querySelector('.js-price-label').textContent = gstCaption;

                var lockIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
                var priceArea = row.querySelector('.js-price-area');
                if (canManagePricing) {
                    priceArea.outerHTML = '<input type="number" class="form-control form-control-sm tm-field text-end js-row-price" name="services[' + i + '][price]" value="' + service.price + '" min="0" step="0.01" style="width: 110px; font-size: .82rem;">';
                    row.querySelector('.js-row-price').addEventListener('input', updateSummary);
                } else {
                    priceArea.outerHTML = '<span class="d-inline-flex align-items-center gap-1 fw-semibold" style="font-size: .85rem;">' + lockIcon + ' ₹' + service.price.toLocaleString('en-IN') + '</span><input type="hidden" name="services[' + i + '][price]" value="' + service.price + '">';
                }

                var hiddenServiceId = document.createElement('input');
                hiddenServiceId.type = 'hidden';
                hiddenServiceId.name = 'services[' + i + '][service_id]';
                hiddenServiceId.value = service.id;
                row.appendChild(hiddenServiceId);

                var assignValueInput = row.querySelector('.js-assign-value');
                if (assignValueInput) {
                    assignValueInput.name = 'services[' + i + '][assigned_to]';
                }

                updateSummary();
            }

            searchInput.addEventListener('focus', function () {
                row.classList.add('is-searching');
                if (searchInput.value.trim()) {
                    renderResults(searchInput.value);
                }
            });

            searchInput.addEventListener('input', function () {
                renderResults(searchInput.value);
            });

            searchInput.addEventListener('keydown', function (e) {
                if (dropdown.classList.contains('d-none') || ! currentResults.length) {
                    return;
                }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, currentResults.length - 1);
                    highlightActive();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    highlightActive();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (activeIndex >= 0) {
                        selectService(currentResults[activeIndex]);
                    }
                } else if (e.key === 'Escape') {
                    closeDropdown();
                }
            });

            document.addEventListener('click', function (e) {
                if (! row.contains(e.target)) {
                    closeDropdown();
                }
            });

            row.querySelector('.js-remove-row').addEventListener('click', function () {
                row.remove();
                updateSummary();
            });

            function setAssignment(empId) {
                var emp = empId ? employeesById[empId] : null;
                row.querySelector('.js-assign-value').value = empId || '';
                row.querySelector('.js-assign-avatar').innerHTML = avatarHtml(emp);
                row.querySelector('.js-assign-name').textContent = emp ? assignmentLabel(emp) : 'Unassigned';
                row.querySelector('.js-assign-you').classList.toggle('d-none', !(emp && String(emp.id) === String(currentUserId)));
                row.querySelector('.js-assign-toggle').style.borderColor = emp ? '' : '#dc3545';
            }

            row.querySelectorAll('.js-assign-option').forEach(function (option) {
                option.addEventListener('click', function (e) {
                    e.preventDefault();
                    setAssignment(option.dataset.id || null);
                });
            });

            if (canAssignTickets && employees.length) {
                setAssignment(currentUserIsEmployee ? currentUserId : null);
            }

            if (initialServiceId && servicesById[initialServiceId]) {
                selectService(servicesById[initialServiceId]);
            }

            return row;
        }

        document.getElementById('addServiceBtn').addEventListener('click', function () {
            var row = createServiceRow();
            row.querySelector('.js-service-search').focus();
        });

        if (document.getElementById('discountInput')) {
            document.getElementById('discountInput').addEventListener('input', updateSummary);
        }

        // Re-add services from old() input after a validation error.
        oldServices.forEach(function (line) {
            if (line.service_id) {
                createServiceRow(String(line.service_id));
            }
        });

        if (! rowsContainer.children.length) {
            createServiceRow();
        }

        document.getElementById('enquiryForm').addEventListener('submit', function (e) {
            var hasClient = customerIdInput.value
                || (clientPhoneInput.value.length === 10 && clientNameInput.value.trim());
            if (! hasClient) {
                e.preventDefault();
                alert('Please enter the client’s mobile number and name.');
                return;
            }
            if (rowsContainer.querySelectorAll('.tm-enquiry-service-row[data-service-id]').length === 0) {
                e.preventDefault();
                alert('Add at least one service.');
            }
        });
    })();
</script>
@endpush
