@extends('layouts.guest')

@section('title', 'Set Password — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                <circle cx="12" cy="16" r="1"></circle>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Set your password</h1>
        <p class="tm-muted small mb-0">
            Code verified. Choose a password for your account.
        </p>
    </div>

    <p class="small tm-muted mb-4 text-center">UI preview only — this form is wired up once the Customer Portal backend is approved and built.</p>

    <form novalidate>
        <div class="mb-3">
            <label class="tm-field-label d-block">New password <span class="text-danger">*</span></label>
            <input type="password" class="form-control tm-field" placeholder="At least 8 characters">
        </div>
        <div class="mb-4">
            <label class="tm-field-label d-block">Confirm password <span class="text-danger">*</span></label>
            <input type="password" class="form-control tm-field" placeholder="Re-enter password">
        </div>

        <button type="button" class="btn btn-tm-primary w-100">Save Password &amp; Continue</button>
    </form>
</div>
@endsection
