@extends('layouts.app')

@section('title', 'Add Admin — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Add Admin Account" subtitle="Give someone Admin access to the system" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Admin Accounts', 'url' => route('admin.admin-accounts.index')], ['label' => 'Add Admin']]" />

<div class="tm-card p-4 admin-account-form">
    <p class="admin-account-note mb-4">Set a password for them below and give it to them. They can sign in straight away and change it later from <b>Change Password</b>.</p>

    <form method="POST" action="{{ route('admin.admin-accounts.store') }}">
        @csrf

        <div class="mb-3">
            <label class="tm-field-label d-block" for="acctName">Full name <span class="text-danger">*</span></label>
            <input type="text" id="acctName" name="name" maxlength="120" value="{{ old('name') }}" class="form-control tm-field @error('name') is-invalid @enderror" placeholder="e.g. Neha Verma" required>
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="tm-field-label d-block" for="acctEmail">Email <span class="text-danger">*</span></label>
            <input type="email" id="acctEmail" name="email" maxlength="190" value="{{ old('email') }}" class="form-control tm-field @error('email') is-invalid @enderror" placeholder="name@firm.com" required>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label class="tm-field-label d-block" for="acctPassword">Password <span class="text-danger">*</span></label>
                <input type="password" id="acctPassword" name="password" autocomplete="new-password" class="form-control tm-field @error('password') is-invalid @enderror" placeholder="At least 8 characters" minlength="8" required>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-6">
                <label class="tm-field-label d-block" for="acctPasswordConfirm">Confirm password <span class="text-danger">*</span></label>
                <input type="password" id="acctPasswordConfirm" name="password_confirmation" autocomplete="new-password" class="form-control tm-field" required>
            </div>
        </div>
        <div class="form-check form-switch mb-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="acctActive" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
            <label class="form-check-label small" for="acctActive">Active</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-tm-primary">Save Admin</button>
            <a href="{{ route('admin.admin-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
