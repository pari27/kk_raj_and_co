@extends('layouts.app')

@section('title', 'Edit Client — ' . config('app.name', 'Task Management'))


@section('content')
<div id="client-form-page">
<x-page-header title="Edit Client" subtitle="Update client details and contact information" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Clients', 'url' => route('customers.index')], ['label' => 'Edit client']]">
    <x-slot:actions>
        <a href="{{ route('customers.index') }}" class="btn px-4 py-2 fw-semibold u-b1aa27">&larr; Clients</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 align-items-start">
        <div class="col-12 col-xl-8">
            <div class="tm-card client-panel p-0">
                <div class="client-panel-title client-panel-title-navy">Client details</div>
                <form method="POST" action="{{ route('customers.update', $customer) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="client-panel-body">
                        <div class="mb-3">
                            <label for="clientName" class="client-form-label">Full name / business name <span class="text-danger">*</span></label>
                            <input id="clientName" type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control tm-field client-form-control @error('name') is-invalid @enderror" placeholder="e.g. Meera Traders" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="clientPhone" class="client-form-label">Mobile number <span class="text-danger">*</span></label>
                                <div class="d-flex">
                                    <span class="client-phone-prefix">+91</span>
                                    <input id="clientPhone" type="text" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="10" inputmode="numeric" class="form-control tm-field client-form-control client-phone-input @error('phone') is-invalid @enderror" placeholder="10-digit mobile number" required>
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
                            <div class="col-12 col-md-6">
                                <label for="clientEmail" class="client-form-label">Email <span class="tm-muted fw-normal">(optional)</span></label>
                                <input id="clientEmail" type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control tm-field client-form-control @error('email') is-invalid @enderror" placeholder="name@example.com">
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
                            <input class="form-check-input" type="checkbox" value="1" id="clientNotifyCheckbox" name="email_notifications_enabled" {{ old('email_notifications_enabled', $customer->email_notifications_enabled) ? 'checked' : '' }} {{ old('email', $customer->email) ? '' : 'disabled' }}>
                            <label class="form-check-label fw-semibold u-8ae449" for="clientNotifyCheckbox">Send email notifications to this client</label>
                            <div class="u-de920b" id="clientNotifyCaption"></div>
                        </div>

                        <div class="form-check form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input flex-shrink-0 u-88b2c2" type="checkbox" role="switch" id="custActive" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                            <label class="mb-0 small" for="custActive">Active</label>
                        </div>


                    </div>
                    <div class="client-form-footer">
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                        <button type="submit" class="btn btn-tm-primary px-4 py-2 fw-semibold">Save changes</button>
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
    var ignoreId = @json($customer->id);
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
    var ignoreId = @json($customer->id);
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
                        text.textContent = 'No client registered with this mobile no.';
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
</script>
@endpush
