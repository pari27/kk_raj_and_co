@extends('layouts.app')

@section('title', 'Add Client — ' . config('app.name', 'Task Management'))


@section('content')
<div id="client-form-page">
<x-page-header title="Add Client" subtitle="Create a client profile and contact details" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Clients', 'url' => route('customers.index')], ['label' => 'New client']]">
    <x-slot:actions>
        <a href="{{ route('customers.index') }}" class="btn px-4 py-2 fw-semibold u-b1aa27">&larr; Clients</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 align-items-start">
        <div class="col-12 col-xl-8">
            <div class="tm-card client-panel p-0">
                <div class="client-panel-title client-panel-title-navy">Client details</div>
                <form method="POST" action="{{ route('customers.store') }}" novalidate>
                    @csrf
                    <div class="client-panel-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="clientPhone" class="client-form-label">Mobile number <span class="text-danger">*</span></label>
                                <div class="d-flex">
                                    <span class="client-phone-prefix">+91</span>
                                    <input id="clientPhone" type="text" name="phone" value="{{ old('phone') }}" maxlength="10" inputmode="numeric" class="form-control tm-field client-form-control client-phone-input @error('phone') is-invalid @enderror" placeholder="10-digit mobile number" required>
                                </div>
                                @error('phone')
                                    @if($message !== 'This mobile number is already registered to another client.')
                                        <div class="invalid-feedback d-block" id="clientPhoneServerError">{{ $message }}</div>
                                    @endif
                                @enderror
                                <div class="tm-validation-hint" id="clientPhoneHint" aria-live="polite">
                                    <span id="clientPhoneHintIcon"></span>
                                    <span id="clientPhoneHintText"></span>
                                </div>
                            </div>
                        </div>

                        <div id="clientLookupBanner" class="client-lookup-banner d-none mb-3" aria-live="polite"></div>

                        <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="clientName" class="client-form-label">Full name / business name <span class="text-danger">*</span></label>
                            <div class="tm-field-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><input id="clientName" type="text" name="name" value="{{ old('name') }}" class="form-control tm-field client-form-control @error('name') is-invalid @enderror" placeholder="e.g. Meera Traders" required></div>
                            <div class="tm-muted d-none client-record-caption" id="clientNameCaption">From the client record</div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                                <label for="clientEmail" class="client-form-label">Email <span class="tm-muted fw-normal">(optional)</span></label>
                                <div class="tm-field-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><polyline points="3 7 12 13 21 7"></polyline></svg><input id="clientEmail" type="email" name="email" value="{{ old('email') }}" class="form-control tm-field client-form-control @error('email') is-invalid @enderror" placeholder="name@example.com"></div>
                                <div class="tm-muted d-none client-record-caption" id="clientEmailCaption">From the client record</div>
                                @error('email')
                                    @if($message !== 'This email is already registered.')
                                        <div class="invalid-feedback d-block text-danger fst-italic" id="clientEmailServerError">{{ $message }}</div>
                                    @endif
                                @enderror
                                <div class="tm-validation-hint" id="clientEmailHint" aria-live="polite">
                                    <span id="clientEmailHintIcon"></span>
                                    <span id="clientEmailHintText"></span>
                                </div>
                            </div>

                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="clientNotifyCheckbox" name="email_notifications_enabled" {{ old('email_notifications_enabled') ? 'checked' : '' }} {{ old('email') ? '' : 'disabled' }}>
                            <label class="form-check-label fw-semibold u-8ae449" for="clientNotifyCheckbox">Send email notifications to this client</label>
                            <div class="u-de920b" id="clientNotifyCaption"></div>
                        </div>

                        <div class="form-check form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input flex-shrink-0 u-88b2c2" type="checkbox" role="switch" id="custActive" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="mb-0 small" for="custActive">Active</label>
                        </div>


                    </div>
                    <div class="client-form-footer">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                        <button type="submit" id="clientSubmit" class="btn btn-tm-primary px-4 py-2 fw-semibold">Save client</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <section class="tm-card client-panel p-0 mb-3">
                <div class="client-panel-title client-panel-title-navy">Portal access</div>
                <div class="client-panel-body">
                    <div class="client-step">
                        <span class="client-step-number">1</span>
                        <div><strong>Client record is saved</strong><div class="tm-muted">when you submit this form</div></div>
                    </div>
                    <div class="client-step">
                        <span class="client-step-number">2</span>
                        <div><strong>Use the client email</strong><div class="tm-muted">as the portal login address</div></div>
                    </div>
                    <div class="client-step">
                        <span class="client-step-number">3</span>
                        <div><strong>Portal sign-in uses OTP</strong><div class="tm-muted">a one-time code verifies the email</div></div>
                    </div>
                </div>
            </section>

            <section class="tm-card client-panel p-0">
                <div class="client-panel-title client-panel-title-green">Mobile number rules</div>
                <div class="client-panel-body">
                    <div class="client-rule"><span class="client-rule-mark">✓</span><span>Enter 10 digits, starting with 6, 7, 8, or 9.</span></div>
                    <div class="client-rule"><span class="client-rule-mark">✓</span><span>Enter the 10-digit number without the +91 prefix.</span></div>
                    <div class="client-rule"><span class="client-rule-mark">✓</span><span>Each mobile number belongs to one client.</span></div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('clientEmail');
    var hint = document.getElementById('clientEmailHint');
    if (! input || ! hint) { return; }

    var notifyCheckbox = document.getElementById('clientNotifyCheckbox');
    var notifyCaption = document.getElementById('clientNotifyCaption');
    var syncNotifyCheckbox = function () {
        var hasEmail = input.value.trim().length > 0;
        notifyCheckbox.disabled = ! hasEmail;
        if (! hasEmail) {
            notifyCheckbox.checked = false;
            notifyCaption.textContent = "Add an email to turn this on. Without an email the client can't log in to the portal, so staff upload documents for them.";
            notifyCaption.style.color = '#8a5a16';
        } else {
            notifyCaption.textContent = 'Portal link, document list, status and payment confirmation';
            notifyCaption.style.color = '#1f6b30';
        }
    };
    if (notifyCheckbox) {
        input.addEventListener('input', syncNotifyCheckbox);
        syncNotifyCheckbox();
    }

    var error = document.getElementById('clientEmailServerError');
    var hasServerEmailError = @json($errors->has('email'));
    var icon = document.getElementById('clientEmailHintIcon');
    var text = document.getElementById('clientEmailHintText');
    var url = @json(route('customers.check-email'));
    var ignoreId = null;
    var timer = null;
    var token = 0;
    var check = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    var cross = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="18" x2="18" y2="6"></line></svg>';

    function checkAvailability() {
        var email = input.value.trim();
        window.clearTimeout(timer);
        token++;
        input.classList.remove('is-invalid', 'is-valid');

        if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            hint.classList.remove('is-visible', 'is-invalid-hint');
            icon.innerHTML = '';
            text.textContent = '';
            return;
        }

        hint.classList.add('is-visible');
        hint.classList.remove('is-invalid-hint');
        icon.innerHTML = '';
        text.textContent = 'Checking availability...';
        var currentToken = token;

        timer = window.setTimeout(function () {
            var query = new URLSearchParams({ email: email });
            if (ignoreId) { query.set('ignore', ignoreId); }
            fetch(url + '?' + query.toString(), { headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (! response.ok) { throw new Error('Availability check failed.'); }
                    return response.json();
                })
                .then(function (data) {
                    if (currentToken !== token) { return; }
                    if (error) { error.hidden = true; }
                    if (data.available) {
                                        hint.classList.remove('is-invalid-hint');
                        input.classList.add('is-valid');
                        icon.innerHTML = check;
                        text.textContent = 'No client registered with this email-id';
                    } else if (window.existingClientFound) {
                        hint.classList.remove('is-visible', 'is-invalid-hint');
                        input.classList.remove('is-valid', 'is-invalid');
                    } else {
                        hint.classList.add('is-invalid-hint');
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        icon.innerHTML = cross;
                        text.textContent = 'This email is already registered';
                    }
                })
                .catch(function () {
                    if (currentToken !== token) { return; }
                    hint.classList.remove('is-visible');
                });
        }, 400);
    }

    input.addEventListener('input', function () {
        if (error) { error.hidden = true; }
        checkAvailability();
    });
    if (input.value.trim() !== '' && ! hasServerEmailError) { checkAvailability(); }
})();

(function () {
    var input = document.getElementById('clientPhone');
    var hint = document.getElementById('clientPhoneHint');
    if (! input || ! hint) { return; }

    var error = document.getElementById('clientPhoneServerError');
    var hasServerPhoneError = @json($errors->has('phone'));
    var icon = document.getElementById('clientPhoneHintIcon');
    var text = document.getElementById('clientPhoneHintText');
    var url = @json(route('customers.check-phone'));
    var timer = null;
    var token = 0;
    var check = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    var cross = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="18" x2="18" y2="6"></line></svg>';

    function checkAvailability() {
        var phone = input.value.trim();
        window.clearTimeout(timer);
        token++;
        input.classList.remove('is-invalid', 'is-valid');

        if (! /^[6-9]\d{9}$/.test(phone)) {
            hint.classList.remove('is-visible', 'is-invalid-hint');
            icon.innerHTML = '';
            text.textContent = '';
            return;
        }

        hint.classList.add('is-visible');
        hint.classList.remove('is-invalid-hint');
        icon.innerHTML = '';
        text.textContent = 'Checking availability...';
        var currentToken = token;

        timer = window.setTimeout(function () {
            var query = new URLSearchParams({ phone: phone });
            fetch(url + '?' + query.toString(), { headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (! response.ok) { throw new Error('Availability check failed.'); }
                    return response.json();
                })
                .then(function (data) {
                    if (currentToken !== token) { return; }
                    if (error) { error.hidden = true; }
                    if (data.available) {
                        hint.classList.remove('is-invalid-hint');
                        input.classList.add('is-valid');
                        icon.innerHTML = check;
                        text.textContent = 'No client registered with this mobile no.';
                    } else if (window.existingClientFound) {
                        hint.classList.remove('is-visible', 'is-invalid-hint');
                        input.classList.remove('is-valid', 'is-invalid');
                    } else {
                        hint.classList.add('is-invalid-hint');
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        icon.innerHTML = cross;
                        text.textContent = 'This mobile number is already registered to another client.';
                    }
                })
                .catch(function () {
                    if (currentToken !== token) { return; }
                    hint.classList.remove('is-visible');
                });
        }, 400);
    }

    input.addEventListener('input', function () {
        if (error) { error.hidden = true; }
        checkAvailability();
    });
    if (input.value.trim() !== '' && ! hasServerPhoneError) { checkAvailability(); }
})();

(function () {
    var phoneInput = document.getElementById('clientPhone');
    var nameInput = document.getElementById('clientName');
    var emailInput = document.getElementById('clientEmail');
    var banner = document.getElementById('clientLookupBanner');
    var submit = document.getElementById('clientSubmit');
    var notify = document.getElementById('clientNotifyCheckbox');
    var nameCaption = document.getElementById('clientNameCaption');
    var emailCaption = document.getElementById('clientEmailCaption');
    if (! phoneInput || ! banner || ! submit) { return; }

    var lookupUrl = @json(route('customers.lookup'));
    var timer = null;
    var token = 0;
    var filled = false;
    var phoneHint = document.getElementById('clientPhoneHint');
    var emailHint = document.getElementById('clientEmailHint');

    function clearExisting() {
        window.existingClientFound = false;
        banner.classList.add('d-none');
        banner.textContent = '';
        submit.disabled = false;
        if (filled) {
            nameInput.value = '';
            emailInput.value = '';
            nameInput.readOnly = false;
            emailInput.readOnly = false;
            nameCaption.classList.add('d-none');
            emailCaption.classList.add('d-none');
            filled = false;
        }
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function showExisting(client) {
        var initials = client.name.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
        var history = client.tickets_count === 1 ? '1 past service' : client.tickets_count + ' past services';

        banner.textContent = '';
        var row = el('div', 'd-flex align-items-start justify-content-between gap-2');
        var left = el('div', 'd-flex align-items-start gap-2');
        left.appendChild(el('span', 'client-lookup-avatar', initials));
        var text = el('div');
        text.appendChild(el('div', 'fw-bold client-lookup-title', 'Existing client found'));
        text.appendChild(el('div', 'client-lookup-sub', 'Client since ' + client.since + ' · ' + history));
        left.appendChild(text);
        row.appendChild(left);
        var link = el('a', 'fw-semibold text-decoration-none flex-shrink-0 client-lookup-link', 'View client →');
        link.href = client.url;
        row.appendChild(link);
        banner.appendChild(row);
        banner.classList.remove('d-none');

        nameInput.value = client.name;
        emailInput.value = client.email || '';
        nameInput.readOnly = true;
        emailInput.readOnly = true;
        nameCaption.classList.remove('d-none');
        if (client.email) { emailCaption.classList.remove('d-none'); }
        filled = true;
        window.existingClientFound = true;
        if (notify) { notify.checked = !! client.email_notifications_enabled; }
        [phoneInput, emailInput].forEach(function (field) { field.classList.remove('is-invalid', 'is-valid'); });
        [phoneHint, emailHint].forEach(function (hint) { if (hint) { hint.classList.remove('is-visible', 'is-invalid-hint'); } });
        var serverError = document.getElementById('clientPhoneServerError');
        if (serverError) { serverError.hidden = true; }
        submit.disabled = true;
    }

    phoneInput.addEventListener('input', function () {
        window.clearTimeout(timer);
        token++;
        clearExisting();

        var phone = phoneInput.value.trim();
        if (! /^\d{10}$/.test(phone)) { return; }

        var current = token;
        timer = window.setTimeout(function () {
            fetch(lookupUrl + '?phone=' + encodeURIComponent(phone), { headers: { Accept: 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (current !== token || ! data.customer) { return; }
                    showExisting(data.customer);
                })
                .catch(function () {});
        }, 400);
    });

    if (/^\d{10}$/.test(phoneInput.value.trim())) {
        phoneInput.dispatchEvent(new Event('input'));
    }
})();
</script>
@endpush
