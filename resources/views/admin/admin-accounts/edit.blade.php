@extends('layouts.app')

@section('title', 'Edit Admin — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Edit Admin Account" :subtitle="$account->email" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Admin Accounts', 'url' => route('admin.admin-accounts.index')], ['label' => 'Edit Admin']]" />

<div class="tm-card p-4 admin-account-form">
    <form method="POST" action="{{ route('admin.admin-accounts.update', $account) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="tm-field-label d-block" for="acctName">Full name <span class="text-danger">*</span></label>
            <input type="text" id="acctName" name="name" maxlength="120" value="{{ old('name', $account->name) }}" class="form-control tm-field @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="tm-field-label d-block" for="acctEmail">Email <span class="text-danger">*</span></label>
            <input type="email" id="acctEmail" name="email" maxlength="190" value="{{ old('email', $account->email) }}" class="form-control tm-field @error('email') is-invalid @enderror" required>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="form-check form-switch mb-1">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="acctActive" name="is_active" value="1" {{ old('is_active', $account->is_active ? '1' : '0') ? 'checked' : '' }}>
            <label class="form-check-label small" for="acctActive">Active (can sign in)</label>
        </div>
        @error('is_active')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
        <div class="form-text tm-muted mb-4">At least one Admin must stay active.</div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-tm-primary">Save Changes</button>
            <a href="{{ route('admin.admin-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
