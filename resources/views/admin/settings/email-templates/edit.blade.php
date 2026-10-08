@extends('layouts.app')

@section('title', 'Edit Email Template — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Edit Email Template" subtitle="Template #{{ $template }}" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Email Templates', 'url' => route('admin.settings.email-templates.index')], ['label' => 'Edit template']]" />

<div class="tm-card p-4" style="max-width: 720px;">
    <p class="small tm-muted mb-4">UI preview only — saving is wired up once this backend is approved and built.</p>

    <form>
        <div class="mb-3">
            <label class="tm-field-label d-block">Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control tm-field" value="Your one-time login code">
        </div>
        <div class="mb-3">
            <label class="tm-field-label d-block">Body <span class="text-danger">*</span></label>
            <textarea class="form-control tm-field" rows="8">Hello @{{customer_name}},

Your one-time login code is @{{otp_code}}. It expires in 10 minutes.

Regards,
@{{firm_name}}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-tm-primary">Save Template</button>
            <a href="{{ route('admin.settings.email-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
