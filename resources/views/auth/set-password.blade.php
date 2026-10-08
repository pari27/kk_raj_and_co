@extends('layouts.guest')

@section('title', 'Set your password — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <div class="tm-badge-icon mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="h4 text-white fw-semibold mb-1">Welcome, {{ $employee->name }}</h1>
        <p class="tm-muted small mb-0">Set a password to finish setting up your account.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="password" class="tm-field-label d-block">New password <span class="text-danger">*</span></label>
            <input id="password" type="password" name="password" class="form-control tm-field" required autofocus autocomplete="new-password">
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="tm-field-label d-block">Confirm password <span class="text-danger">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control tm-field" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-tm-primary w-100">Set password &amp; sign in</button>
    </form>
</div>
@endsection
