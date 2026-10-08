@extends('layouts.guest')

@section('title', 'Client Login — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                <path d="m22 6-10 7L2 6"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Client Login</h1>
        <p class="tm-muted small mb-0">
            {{ config('app.firm_name', '[Firm name]') }} client portal
        </p>
    </div>

    <p class="small tm-muted mb-4 text-center">UI preview only — this form is wired up once the Customer Portal backend is approved and built.</p>

    <form novalidate>
        <div class="mb-3">
            <label for="email" class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <input id="email" type="email" class="form-control tm-field" placeholder="you@example.com" autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="tm-field-label d-block">Password <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center gap-2">
                <input id="password" type="password" class="form-control tm-field" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                <button type="button" class="tm-submit-circle" aria-label="Log in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <p class="text-center small mb-0">
            <a href="{{ route('customer.verify-otp') }}" class="tm-link text-decoration-underline">First login or forgot password? Verify with OTP</a>
        </p>
    </form>

    <div class="tm-divider my-4">OR</div>

    <p class="text-center tm-muted small mb-0">
        <a href="{{ route('login') }}" class="tm-link text-decoration-underline">I'm a firm employee — go to employee login</a>
    </p>
</div>
@endsection
