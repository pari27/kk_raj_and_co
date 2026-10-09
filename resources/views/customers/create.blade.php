@extends('layouts.app')

@section('title', 'Add Client — ' . config('app.name', 'Task Management'))

@push('styles')
<style>
    #client-form-page .client-panel {
        overflow: hidden;
        border-radius: .85rem;
    }

    #client-form-page .client-panel-title {
        padding: .85rem 1.15rem;
        color: #fff;
        font-size: 1.05rem;
        font-weight: 700;
    }

    #client-form-page .client-panel-title-navy {
        background: #101b3d;
    }

    #client-form-page .client-panel-title-blue {
        background: #24569b;
    }

    #client-form-page .client-panel-title-green {
        background: #1f6b30;
    }

    #client-form-page .client-panel-body {
        padding: 1.25rem 1.4rem;
    }

    #client-form-page .client-form-label {
        display: block;
        margin-bottom: .45rem;
        font-size: .8rem;
        font-weight: 700;
    }

    #client-form-page .client-form-control {
        border-radius: .375rem;
        font-size: .8rem;
    }

    #client-form-page .client-phone-prefix {
        display: flex;
        align-items: center;
        padding: .375rem .75rem;
        border: 1px solid #d9dfe8;
        border-right: 0;
        border-radius: .375rem 0 0 .375rem;
        background: #fff;
        color: #6b7280;
        font-size: .8rem;
        font-weight: 600;
        line-height: 1.5;
    }

    #client-form-page .client-phone-input {
        border-radius: 0 .375rem .375rem 0;
    }

    #client-form-page .client-active-row {
        padding: .9rem 1rem;
        border: 1px solid #e2e7ed;
        border-radius: .65rem;
    }

    #client-form-page .client-active-row .form-check-input {
        width: 3rem;
        height: 1.6rem;
        margin-top: 0;
        cursor: pointer;
    }

    #client-form-page .client-active-row .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    #client-form-page .client-step {
        display: flex;
        align-items: flex-start;
        gap: .8rem;
    }

    #client-form-page .client-step + .client-step {
        margin-top: 1rem;
    }

    #client-form-page .client-step-number {
        width: 2.1rem;
        height: 2.1rem;
        flex: 0 0 2.1rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #e7effc;
        color: #24569b;
        font-weight: 700;
    }

    #client-form-page .client-rule {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        color: #4b5563;
    }

    #client-form-page .client-rule + .client-rule {
        margin-top: .8rem;
    }

    #client-form-page .client-rule-mark {
        color: #29995a;
        font-weight: 700;
    }

    #client-form-page .client-step,
    #client-form-page .client-step *,
    #client-form-page .client-rule,
    #client-form-page .client-rule * {
        font-size: .8rem;
    }
    #client-form-page .client-form-footer {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .6rem;
        padding: 1rem 1.4rem;
        border-top: 1px solid #edf0f3;
        background: #f8f9fb;
    }

    @media (max-width: 575.98px) {
        #client-form-page .client-panel-body {
            padding: 1rem;
        }

        #client-form-page .client-form-footer {
            padding: .85rem 1rem;
        }

        #client-form-page .client-form-footer .btn {
            flex: 1 1 auto;
        }
    }
</style>
@endpush

@section('content')
<div id="client-form-page">
<x-page-header title="Add Client" subtitle="Create a client profile and contact details" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Clients', 'url' => route('customers.index')], ['label' => 'New client']]">
    <x-slot:actions>
        <a href="{{ route('customers.index') }}" class="btn px-4 py-2 fw-semibold" style="background: #101b3d; border-color: #101b3d; color: #fff;">&larr; Clients</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 align-items-start">
        <div class="col-12 col-xl-8">
            <div class="tm-card client-panel p-0">
                <div class="client-panel-title client-panel-title-navy">Client details</div>
                <form method="POST" action="{{ route('customers.store') }}" novalidate>
                    @csrf
                    <div class="client-panel-body">
                        <div class="mb-3">
                            <label for="clientName" class="client-form-label">Full name / business name <span class="text-danger">*</span></label>
                            <input id="clientName" type="text" name="name" value="{{ old('name') }}" class="form-control tm-field client-form-control @error('name') is-invalid @enderror" placeholder="e.g. Meera Traders" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

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
                            <div class="col-12 col-md-6">
                                <label for="clientEmail" class="client-form-label">Email <span class="tm-muted fw-normal">(optional)</span></label>
                                <input id="clientEmail" type="email" name="email" value="{{ old('email') }}" class="form-control tm-field client-form-control @error('email') is-invalid @enderror" placeholder="name@example.com">
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
                            <label class="form-check-label fw-semibold" for="clientNotifyCheckbox" style="font-size: .85rem;">Send email notifications to this client</label>
                        </div>

                        <div class="form-check form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input flex-shrink-0" type="checkbox" role="switch" id="custActive" name="is_active" value="1" style="width: 2.4rem; height: 1.3rem;" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="mb-0 small" for="custActive">Active</label>
                        </div>


                    </div>
                    <div class="client-form-footer">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Cancel</a>
                        <button type="submit" class="btn btn-tm-primary px-4 py-2 fw-semibold">Save client</button>
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
    var syncNotifyCheckbox = function () {
        var hasEmail = input.value.trim().length > 0;
        notifyCheckbox.disabled = ! hasEmail;
        if (! hasEmail) {
            notifyCheckbox.checked = false;
        }
    };
    if (notifyCheckbox) {
        input.addEventListener('input', syncNotifyCheckbox);
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
                        text.textContent = 'Available, used for login';
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
                        text.textContent = 'Available';
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
