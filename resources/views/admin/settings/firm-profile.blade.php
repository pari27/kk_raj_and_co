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
            <p class="small tm-muted mb-4">UI preview only — saving is wired up once this backend is approved and built.</p>

            <form>
                <div class="mb-3">
                    <label class="tm-field-label d-block">Firm name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control tm-field" value="[Firm name]">
                </div>
                <div class="mb-3">
                    <label class="tm-field-label d-block">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control tm-field" rows="2" placeholder="Registered office address"></textarea>
                </div>
                <div class="mb-3">
                    <label class="tm-field-label d-block">Logo</label>
                    <input type="file" class="form-control tm-field">
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-tm-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif

        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Test mode</div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.settings.system.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-3">
                        <input class="form-check-input flex-shrink-0" type="checkbox" role="switch" id="testModeToggle" name="test_mode" value="1" style="width: 2.6rem; height: 1.4rem;" {{ $setting->test_mode ? 'checked' : '' }} onchange="this.form.requestSubmit()">
                        <label class="mb-0 fw-semibold" for="testModeToggle" style="font-size: .85rem;">{{ $setting->test_mode ? 'On' : 'Off' }}</label>
                    </div>
                    <p class="tm-muted mb-0" style="font-size: .8rem;">
                        When on, every email the app sends (employee invitations, password resets, and anything added later) is redirected to
                        <strong>{{ config('mail.test_recipient') ?: 'no address — set MAIL_TEST_RECIPIENT in .env' }}</strong>
                        instead of the real recipient. Turn this off before relying on real notifications going to real clients and staff.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
