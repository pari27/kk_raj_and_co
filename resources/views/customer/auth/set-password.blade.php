@extends('layouts.guest')

@section('title', 'Set your password — ' . config('app.firm_name'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.firm_name') }}" class="d-block mx-auto mb-3" style="height: 26px; width: auto;">
        <h1 class="h4 text-white fw-semibold mb-1">Set your password</h1>
        <p class="tm-muted small mb-0">
            Code verified. Choose a password for your account.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('customer.set-password.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="password" class="tm-field-label d-block">New password <span class="text-danger">*</span></label>
            <input id="password" name="password" type="password" class="form-control tm-field" placeholder="At least 8 characters" required autofocus autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="tm-field-label d-block">Confirm password <span class="text-danger">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control tm-field" placeholder="Re-enter password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-tm-primary w-100">Save password &amp; continue</button>
    </form>
</div>
@endsection
