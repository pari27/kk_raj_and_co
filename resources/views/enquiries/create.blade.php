@extends('layouts.app')

@section('title', 'New Enquiry — ' . config('app.name', 'Task Management'))


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
        <h1 class="tm-serif fw-bold mb-1 u-e83e11">New enquiry</h1>
        <p class="tm-muted mb-0 u-5a305f">Add a client and the services they need</p>
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
                    <span class="small fw-normal u-bbebd4">Start with the mobile number</span>
                </div>
                <div class="p-4">
                    @error('customer_id')
                        <div class="alert alert-danger py-2 small u-cf7e00">{{ $message }}</div>
                    @enderror
                    @error('customer_phone')
                        <div class="alert alert-danger py-2 small u-cf7e00">{{ $message }}</div>
                    @enderror
                    @error('customer_name')
                        <div class="alert alert-danger py-2 small u-cf7e00">{{ $message }}</div>
                    @enderror
                    @error('customer_email')
                        <div class="alert alert-danger py-2 small u-cf7e00">{{ $message }}</div>
                    @enderror


                    <label class="tm-field-label d-block">Mobile number <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <span class="d-flex align-items-center px-3 u-d03817">+91</span>
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
                        <label class="form-check-label fw-semibold u-8ae449" for="clientNotifyCheckbox">Send email notifications to this client</label>
                        <div class="u-0af8f2" id="clientNotifyCaption"></div>
                    </div>
                </div>
            </div>

            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title d-flex align-items-center justify-content-between">
                    <span>2. Services</span>
                    <span class="small fw-normal u-bbebd4">One entry is created for each service</span>
                </div>
                <div class="p-4">
                    @error('services')
                        <div class="alert alert-danger py-2 small u-cf7e00">{{ $message }}</div>
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
                <button type="submit" class="btn btn-tm-primary" id="submitEnquiryBtn">Create <span id="submitTicketCount">0</span> services</button>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="tm-card p-0 mb-3 u-abf627">
                <div class="tm-side-card-header u-1fcbae">Summary</div>
                <div class="p-3 u-8ae449">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="tm-muted">Client</span>
                        <span class="fw-semibold" id="summaryClient">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="tm-muted">Services</span>
                        <span class="fw-semibold" id="summaryServiceCount">0</span>
                    </div>
                    <div id="summaryServiceLines" class="mb-2"></div>

                    <div class="rounded-3 p-3 mt-2 u-389ec9">
                        <div class="fw-bold u-f63532">Total</div>
                        <div class="mb-1 u-c07f9c">including GST</div>
                        <div class="fw-bold u-e95ef8" id="summaryTotal">₹0</div>
                    </div>
                </div>
            </div>

            <div class="tm-whats-next">
                <div class="fw-bold mb-2 u-b5760f">What happens next</div>
                <div class="d-flex gap-2 mb-2 u-5a305f">
                    <span class="text-success">&check;</span>
                    <span id="whatsNextTickets">Services will be created{{ auth()->user()->isEmployee() ? ' and assigned to you' : '' }}</span>
                </div>
                <div class="d-flex gap-2 u-5a305f">
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
                + '<span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 u-3de690">+</span>'
                + '<div><div class="fw-bold u-e02448">New client</div>'
                + '<div class="u-bd1e2f">This number isn’t registered yet. Fill in the name and a new client is created when you save.</div></div>'
                + '</div>';
            clientNameInput.disabled = false;
            clientEmailInput.disabled = false;
            clientNameCaption.classList.add('d-none');
            clientEmailCaption.classList.add('d-none');
            syncSummaryClient();
            updateNotifyState();
        }

        function applyExistingClientState(customer) {
            customerIdInput.value = customer.id;
            clientLookupBanner.style.background = '#eaf7ec';
            clientLookupBanner.style.border = '1px solid #cdeccb';
            var initials = customer.name.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
            var historyText = customer.tickets_count === 1 ? '1 past service' : (customer.tickets_count + ' past services');
            clientLookupBanner.innerHTML = '<div class="d-flex align-items-start justify-content-between gap-2">'
                + '<div class="d-flex align-items-start gap-2">'
                + '<span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 u-191a15">' + initials + '</span>'
                + '<div><div class="fw-bold u-0b7c80">Existing client found</div>'
                + '<div class="u-bd1e2f">Client since ' + customer.since + ' &middot; ' + historyText + '</div></div>'
                + '</div>'
                + '<a href="' + customer.url + '" target="_blank" class="fw-semibold text-decoration-none flex-shrink-0 u-8699ce">View client &rarr;</a>'
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

        // Summary card: show the typed name for a new client (an existing client's name is set when it is picked).
        function syncSummaryClient() {
            if (customerIdInput.value !== '') {
                return;
            }

            document.getElementById('summaryClient').textContent = clientNameInput.value.trim() || '—';
        }

        clientNameInput.addEventListener('input', syncSummaryClient);

        function clearLookupState() {
            customerIdInput.value = '';
            clientLookupBanner.classList.add('d-none');
            clientLookupBanner.innerHTML = '';
            clientNameInput.disabled = false;
            clientEmailInput.disabled = false;
            clientNameCaption.classList.add('d-none');
            clientEmailCaption.classList.add('d-none');
            syncSummaryClient();
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
                    return '<span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 u-d8155b">?</span>';
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
                // The person signed in sees themselves as "Name (me)".
                if (String(assignee.id) === String(currentUserId)) {
                    return assignee.name + ' (me)';
                }

                return assignee.role === 'Employee' ? assignee.name : assignee.role + ' — ' + assignee.name;
            }

            function assignmentLabel(assignee) {
                return assignee.name;
            }

            var assignField = '';
            if (canAssignTickets && employees.length) {
                var optionsHtml = '<li><a class="dropdown-item d-flex align-items-center gap-2 js-assign-option u-5a305f" href="#" data-id="">'
                    + avatarHtml(null) + '<span>Unassigned</span></a></li>';
                employees.forEach(function (e) {
                    optionsHtml += '<li><a class="dropdown-item d-flex align-items-center gap-2 js-assign-option u-5a305f" href="#" data-id="' + e.id + '">'
                        + avatarHtml(e) + '<span>' + escapeHtml(assignmentOptionLabel(e)) + '</span></a></li>';
                });

                assignField = '<div class="flex-shrink-0 u-684a19">'
                    + '<div class="tm-service-row-label">Assign to</div>'
                    + '<div class="dropdown">'
                    + '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 js-assign-toggle u-db3251" data-bs-toggle="dropdown" data-bs-auto-close="outside">'
                    + '<span class="js-assign-avatar">' + avatarHtml(null) + '</span>'
                    + '<span class="js-assign-name">Unassigned</span>'
                    + '<span class="badge rounded-pill text-bg-success js-assign-you d-none u-2a089b">You</span>'
                    + '</button>'
                    + '<ul class="dropdown-menu pt-0 u-0d9a19">'
                    + '<li class="px-2 pt-2 pb-2 mb-1 border-bottom u-cdeac9"><input type="search" class="form-control form-control-sm js-assign-search u-319f32" placeholder="Search..." autocomplete="off"></li>'
                    + optionsHtml
                    + '<li class="js-assign-empty px-3 py-1 tm-muted d-none u-319f32">No match</li>'
                    + '</ul>'
                    + '</div>'
                    + '<input type="hidden" class="js-assign-value" value="">'
                    + '</div>';
            }

            row.innerHTML = '<span class="tm-enquiry-service-icon">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'
                + '</span>'
                + '<div class="flex-grow-1 tm-service-search-wrap">'
                + '<label class="tm-service-row-label">Service <span class="text-danger">*</span></label>'
                + '<div class="js-service-name-display fw-semibold d-none u-8ae449"></div>'
                + '<div class="tm-field-icon js-service-search-field">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>'
                + '<input type="text" class="form-control tm-field js-service-search" placeholder="Search and select a service" autocomplete="off">'
                + '</div>'
                + '<div class="tm-muted js-service-hint u-42e121">Documents and price fill in after you pick a service</div>'
                + '<div class="js-service-docs d-none u-6a3042"></div>'
                + '<div class="tm-service-dropdown d-none"></div>'
                + '</div>'
                + assignField
                + '<div class="text-end flex-shrink-0 u-fc5653">'
                + '<div class="tm-service-row-label js-price-label">Price</div>'
                + '<div class="js-price-area tm-muted u-8ae449">&#8377; —</div>'
                + '</div>'
                + '<button type="button" class="tm-remove-doc js-remove-row u-994c3e" title="Remove">&times;</button>';

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

                if (! currentResults.length) {
                    dropdown.innerHTML = '<div class="tm-service-dropdown-empty">' + (q ? 'No services match "' + q + '"' : 'No more services to add') + '</div>';
                    dropdown.classList.remove('d-none');
                    return;
                }

                var html = '<div class="tm-service-dropdown-header">' + currentResults.length
                    + (q
                        ? (currentResults.length === 1 ? ' service matches "' : ' services match "') + q + '"'
                        : (currentResults.length === 1 ? ' service' : ' services')) + '</div>';
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
                    priceArea.outerHTML = '<input type="number" class="form-control form-control-sm tm-field text-end js-row-price u-ab4078" name="services[' + i + '][price]" value="' + service.price + '" min="0" step="0.01">';
                    row.querySelector('.js-row-price').addEventListener('input', updateSummary);
                } else {
                    priceArea.outerHTML = '<span class="d-inline-flex align-items-center gap-1 fw-semibold u-8ae449">' + lockIcon + ' ₹' + service.price.toLocaleString('en-IN') + '</span><input type="hidden" name="services[' + i + '][price]" value="' + service.price + '">';
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
                renderResults(searchInput.value);
            });

            // Clicking the box again (after Esc or a pick elsewhere) reopens the list.
            searchInput.addEventListener('click', function () {
                if (dropdown.classList.contains('d-none')) {
                    row.classList.add('is-searching');
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

                    var toggle = row.querySelector('.js-assign-toggle');
                    var instance = window.bootstrap && bootstrap.Dropdown.getInstance(toggle);
                    if (instance) { instance.hide(); }
                });
            });

            var assignSearch = row.querySelector('.js-assign-search');
            if (assignSearch) {
                var assignToggle = row.querySelector('.js-assign-toggle');

                // Open the list above the card (fixed positioning) so it isn't clipped by it.
                if (window.bootstrap) {
                    bootstrap.Dropdown.getOrCreateInstance(assignToggle, {
                        popperConfig: function (defaultConfig) {
                            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
                        },
                    });
                }
                var assignEmpty = row.querySelector('.js-assign-empty');

                assignSearch.addEventListener('input', function () {
                    var query = assignSearch.value.trim().toLowerCase();
                    var visible = 0;
                    row.querySelectorAll('.js-assign-option').forEach(function (option) {
                        var match = option.textContent.toLowerCase().indexOf(query) !== -1;
                        option.parentElement.classList.toggle('d-none', !match);
                        if (match) { visible++; }
                    });
                    assignEmpty.classList.toggle('d-none', visible > 0);
                });

                assignToggle.addEventListener('shown.bs.dropdown', function () {
                    assignSearch.value = '';
                    assignSearch.dispatchEvent(new Event('input'));
                    assignSearch.focus();
                });
            }

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

        syncSummaryClient();

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
