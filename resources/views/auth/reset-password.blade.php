@extends('layouts.guest')

@section('title', 'Reset password — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <img src="{{ config('app.firm_logo_url') }}" alt="{{ config('app.firm_name') }}" class="d-block mx-auto mb-3 u-a60e1d">
        <h1 class="h4 text-white fw-semibold mb-1">Reset your password</h1>
        <p class="tm-muted small mb-0">Choose a new password for your account.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.reset.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="password" class="tm-field-label d-block">New password <span class="text-danger">*</span></label>
            <input id="password" type="password" name="password" class="form-control tm-field" required autofocus autocomplete="new-password">
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="tm-field-label d-block">Confirm password <span class="text-danger">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control tm-field" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-tm-primary w-100">Reset password</button>
    </form>
</div>
@endsection
