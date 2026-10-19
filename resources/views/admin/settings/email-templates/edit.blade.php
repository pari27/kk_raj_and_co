@extends('layouts.app')

@section('title', 'Edit Email Template — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Edit Email Template" :subtitle="$template->key" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Settings'], ['label' => 'Email Templates', 'url' => route('admin.settings.email-templates.index')], ['label' => 'Edit template']]" />

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="tm-card p-4">
            <form method="POST" action="{{ route('admin.settings.email-templates.update', $template) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="tm-field-label d-block" for="templateSubject">Subject <span class="text-danger">*</span></label>
                    <input type="text" id="templateSubject" name="subject" value="{{ old('subject', $template->subject) }}" maxlength="255" class="form-control tm-field @error('subject') is-invalid @enderror">
                    @error('subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="tm-field-label d-block" for="templateBody">Body <span class="text-danger">*</span></label>
                    <textarea id="templateBody" name="body" rows="10" maxlength="10000" class="form-control tm-field @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                    @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-tm-primary">Save Template</button>
                    <a href="{{ route('admin.settings.email-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="tm-card p-0 overflow-hidden">
            <div class="tm-section-title">Placeholders</div>
            <div class="p-3">
                <p class="tm-muted mb-3 u-cf7e00">
                    Click a placeholder to insert it where the cursor is. It is replaced with the real value when the email is sent.
                </p>
                @foreach ($template->variables() as $name => $description)
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary js-insert-placeholder u-cc37c5" data-placeholder="@{{{{ $name }}}}"><code>@{{{{ $name }}}}</code></button>
                        <span class="tm-muted text-end u-cc37c5">{{ $description }}{{ in_array($name, $template->requiredVariables(), true) ? ' (required)' : '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-insert-placeholder').forEach(function (button) {
        button.addEventListener('click', function () {
            var field = document.getElementById('templateBody');
            var token = button.dataset.placeholder;
            var start = field.selectionStart;
            var end = field.selectionEnd;

            field.value = field.value.slice(0, start) + token + field.value.slice(end);
            field.focus();
            field.selectionStart = field.selectionEnd = start + token.length;
        });
    });
</script>
@endpush
