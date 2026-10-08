@extends('layouts.guest')

@section('title', 'Verify OTP — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Verify with OTP</h1>
        <p class="tm-muted small mb-0">
            We'll email a one-time code to verify it's you.
        </p>
    </div>

    <p class="small tm-muted mb-4 text-center">UI preview only — this form is wired up once the Customer Portal backend is approved and built.</p>

    <form novalidate>
        <div class="mb-3">
            <label class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <input type="email" class="form-control tm-field" placeholder="you@example.com">
                <button type="button" class="btn btn-outline-secondary flex-shrink-0">Send OTP</button>
            </div>
        </div>

        <div class="mb-4">
            <label class="tm-field-label d-block">6-digit code <span class="text-danger">*</span></label>
            <input type="text" maxlength="6" class="form-control tm-field text-center" placeholder="••••••" style="letter-spacing: .5rem; font-size: 1.25rem;">
            <div class="form-text tm-muted">Code expires in 10 minutes.</div>
        </div>

        <button type="button" class="btn btn-tm-primary w-100 mb-3">Verify Code</button>

        <p class="text-center tm-muted small mb-0">
            <a href="{{ route('customer.login') }}" class="tm-link text-decoration-underline">Back to login</a>
        </p>
    </form>
</div>
@endsection
