@extends('layouts.app')

@section('title', 'Email Template — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Email Template" :subtitle="$template->key" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Email Templates', 'url' => route('admin.settings.email-templates.index')], ['label' => $template->key]]" />

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="tm-card p-4">
            <div class="mb-3">
                <div class="tm-field-label d-block">Subject</div>
                <div class="form-control tm-field email-template-readonly">{{ $template->subject }}</div>
            </div>

            <div class="mb-3">
                <div class="tm-field-label d-block">Body</div>
                <div class="form-control tm-field email-template-readonly email-template-body">{{ $template->body }}</div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.settings.email-templates.index') }}" class="btn btn-outline-secondary">Back</a>
                <span class="tm-muted small">Email templates can only be changed by the software team.</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Placeholders</div>
            <div class="p-3">
                <p class="tm-muted mb-3 u-cf7e00">
                    A placeholder is replaced with the real value when the email is sent.
                </p>
                @foreach ($template->variables() as $name => $description)
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <code class="u-cc37c5">@{{{{ $name }}}}</code>
                        <span class="tm-muted text-end u-cc37c5">{{ $description }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
