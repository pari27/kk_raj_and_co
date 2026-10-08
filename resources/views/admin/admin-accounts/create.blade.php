@extends('layouts.app')

@section('title', 'Add Admin — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Add Admin Account" subtitle="Grant Admin access to the system" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Admin Accounts', 'url' => route('admin.admin-accounts.index')], ['label' => 'New admin account']]" />

<div class="tm-card p-4" style="max-width: 560px;">
    <p class="small tm-muted mb-4">UI preview only — saving is wired up once this backend is approved and built.</p>

    <form>
        <div class="mb-3">
            <label class="tm-field-label d-block">Full name <span class="text-danger">*</span></label>
            <input type="text" class="form-control tm-field" placeholder="e.g. Neha Verma">
        </div>
        <div class="mb-3">
            <label class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control tm-field" placeholder="name@firm.com">
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="acctActive" checked>
            <label class="form-check-label small" for="acctActive">Active</label>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-tm-primary">Save &amp; Email Credentials</button>
            <a href="{{ route('admin.admin-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
