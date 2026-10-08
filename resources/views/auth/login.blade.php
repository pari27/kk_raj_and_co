@extends('layouts.guest')

@section('title', 'Log in — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                <path d="m8 12 3 3 5-6"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Welcome back</h1>
        <p class="tm-muted small mb-0">
            Log in to {{ config('app.name', 'Task Management') }} &middot; {{ config('app.firm_name', '[Firm name]') }}
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control tm-field"
                placeholder="you@yourfirm.com"
                required
                autofocus
                autocomplete="username"
            >
        </div>

        <div class="mb-3">
            <label for="password" class="tm-field-label d-block">Password <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center gap-2">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control tm-field"
                    placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    required
                    autocomplete="current-password"
                >
                <button type="submit" class="tm-submit-circle" aria-label="Log in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label tm-muted small" for="remember">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="tm-link small text-decoration-underline">Forgot password?</a>
        </div>

        <div class="tm-divider mb-4">OR</div>

        <a href="{{ route('customer.login') }}" class="tm-customer-panel d-flex align-items-center gap-3 mb-4 text-decoration-none">
            <div class="tm-icon-circle">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4a9b3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="m22 6-10 7L2 6"></path>
                </svg>
            </div>
            <div class="flex-grow-1">
                <div class="text-white small fw-semibold">I'm a customer</div>
                <div class="tm-muted small">Log in with the OTP sent to your email</div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b98ac" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"></path>
                <path d="m12 5 7 7-7 7"></path>
            </svg>
        </a>

        <p class="text-center tm-muted small mb-0">
            Need an account? Ask your firm's Admin to add you.
        </p>
    </form>
</div>
@endsection
