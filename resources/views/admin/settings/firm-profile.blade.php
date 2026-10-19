@extends('layouts.app')

@section('title', 'Firm Profile — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Settings" subtitle="Firm profile, branding and system preferences" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Firm Profile']]" />

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><span class="nav-link active">Firm Profile</span></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.email-templates.index') }}">Email Templates</a></li>
</ul>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="tm-card p-4">
            <p class="small tm-muted mb-4">These details appear in the menu, the client portal, the sign-in pages and every email the app sends.</p>

            <form method="POST" action="{{ route('admin.settings.firm-profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="tm-field-label d-block" for="firmNameInput">Firm name <span class="text-danger">*</span></label>
                    <input type="text" id="firmNameInput" name="firm_name" maxlength="120" class="form-control tm-field @error('firm_name') is-invalid @enderror" value="{{ old('firm_name', $setting->firm_name ?: config('app.firm_name')) }}" required>
                    @error('firm_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="tm-field-label d-block" for="firmAddressInput">Address <span class="text-danger">*</span></label>
                    <textarea id="firmAddressInput" name="firm_address" rows="2" maxlength="500" class="form-control tm-field @error('firm_address') is-invalid @enderror" placeholder="Registered office address" required>{{ old('firm_address', $setting->firm_address ?: config('app.firm_address')) }}</textarea>
                    @error('firm_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="tm-field-label d-block" for="firmLogoInput">Logo</label>
                    <div class="firm-logo-preview mb-2"><img src="{{ config('app.firm_logo_url') }}" alt="Current logo"></div>
                    <input type="file" id="firmLogoInput" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control tm-field @error('logo') is-invalid @enderror">
                    <div class="form-text tm-muted">PNG, JPG or WebP, up to 2 MB. Leave empty to keep the current logo.</div>
                    @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @if ($setting->firm_logo_path)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_logo" id="removeLogo" value="1">
                            <label class="form-check-label small" for="removeLogo">Remove my logo and use the default one</label>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-tm-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Email settings</div>
            <form method="POST" action="{{ route('admin.settings.system.update') }}">
                @csrf
                @method('PUT')

                <div class="p-4 border-bottom">
                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-3">
                        <input class="form-check-input flex-shrink-0 u-32106b" type="checkbox" role="switch" id="notificationsEnabledToggle" name="notifications_enabled" value="1" {{ $setting->notifications_enabled ? 'checked' : '' }} onchange="this.form.requestSubmit()">
                        <label class="mb-0 fw-semibold u-8ae449" for="notificationsEnabledToggle">Email notifications: {{ $setting->notifications_enabled ? 'On' : 'Off' }}</label>
                    </div>
                    <p class="tm-muted mb-0 u-5a305f">
                        Master switch for every email this app sends (employee invitations, password resets, and anything added later). Turn off to stop all outgoing email immediately, regardless of Test mode.
                    </p>
                </div>

                <div class="p-4">
                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-3">
                        <input class="form-check-input flex-shrink-0 u-32106b" type="checkbox" role="switch" id="testModeToggle" name="test_mode" value="1" {{ $setting->test_mode ? 'checked' : '' }} onchange="this.form.requestSubmit()">
                        <label class="mb-0 fw-semibold u-8ae449" for="testModeToggle">Test mode: {{ $setting->test_mode ? 'On' : 'Off' }}</label>
                    </div>
                    <p class="tm-muted mb-0 u-5a305f">
                        When on (and email notifications are also on), every email is redirected to
                        <strong>{{ config('mail.test_recipient') ?: 'no address — set MAIL_TEST_RECIPIENT in .env' }}</strong>
                        instead of the real recipient. Turn this off before relying on real notifications going to real clients and staff.
                    </p>
                </div>
            </form>
        </div>

        <div class="tm-card p-0 overflow-hidden mt-3">
            <div class="tm-section-title">Numbering</div>
            <form method="POST" action="{{ route('admin.settings.numbering.update') }}" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-6">
                        <label class="tm-field-label d-block" for="servicePrefixInput">Service number prefix</label>
                        <input type="text" id="servicePrefixInput" name="service_prefix" value="{{ old('service_prefix', $setting->service_prefix) }}" maxlength="6" class="form-control tm-field text-uppercase @error('service_prefix') is-invalid @enderror">
                        @error('service_prefix')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <label class="tm-field-label d-block" for="enquiryPrefixInput">Enquiry number prefix</label>
                        <input type="text" id="enquiryPrefixInput" name="enquiry_prefix" value="{{ old('enquiry_prefix', $setting->enquiry_prefix) }}" maxlength="6" class="form-control tm-field text-uppercase @error('enquiry_prefix') is-invalid @enderror">
                        @error('enquiry_prefix')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-tm-primary mt-3">Save</button>
                <p class="tm-muted mt-2 mb-0 u-cf7e00">
                    New numbers look like <strong>{{ $setting->service_prefix }}-{{ now()->year }}-0001</strong> and <strong>{{ $setting->enquiry_prefix }}-{{ now()->year }}-0001</strong>. Existing numbers are not changed.
                </p>
            </form>
        </div>
    </div>
</div>
@endsection
