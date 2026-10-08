@extends('layouts.guest')

@section('title', 'Forgot password — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Forgot password?</h1>
        <p class="tm-muted small mb-0">
            Enter your email and we'll send you instructions to reset it.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small" role="alert">
            {{ session('status') }}
        </div>
        <p class="text-center mb-0">
            <a href="{{ route('login') }}" class="tm-link small text-decoration-underline">Back to login</a>
        </p>
    @else
        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            <div class="mb-4">
                <label for="email" class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
                <div class="d-flex align-items-center gap-2">
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
                    <button type="submit" class="tm-submit-circle" aria-label="Send reset instructions">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <p class="text-center tm-muted small mb-0">
                <a href="{{ route('login') }}" class="tm-link text-decoration-underline">Back to login</a>
            </p>
        </form>
    @endif
</div>
@endsection
