@extends('layouts.app')

@section('title', 'Firm Profile — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Settings" subtitle="Firm profile, branding and system preferences" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Firm Profile']]" />

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><span class="nav-link active">Firm Profile</span></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.email-templates.index') }}">Email Templates</a></li>
</ul>

<div class="tm-card p-4" style="max-width: 640px;">
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
@endsection
