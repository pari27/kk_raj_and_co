@extends('layouts.guest')

@section('title', 'Get a one-time code — ' . config('app.firm_name'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <img src="{{ config('app.firm_logo_url') }}" alt="{{ config('app.firm_name') }}" class="d-block mx-auto mb-3 u-a60e1d">
        <h1 class="h4 text-white fw-semibold mb-1">Verify it's you</h1>
        <p class="tm-muted small mb-0">
            We'll email a one-time code to set or reset your password.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small" role="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('customer.otp.verify') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control tm-field" placeholder="you@example.com" required autofocus autocomplete="username">
                <button type="submit" formaction="{{ route('customer.otp.send') }}" class="btn btn-outline-secondary flex-shrink-0">Send me a code</button>
            </div>
        </div>

        <div class="mb-4">
            <label for="code" class="tm-field-label d-block">6-digit code <span class="text-danger">*</span></label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-control tm-field text-center u-1a58d1" placeholder="••••••">
            <div class="form-text tm-muted">The code expires in 10 minutes and works once.</div>
        </div>

        <button type="submit" class="btn btn-tm-primary w-100 mb-3">Verify code</button>

        <p class="text-center tm-muted small mb-0">
            <a href="{{ route('customer.login') }}" class="tm-link text-decoration-underline">Back to login</a>
        </p>
    </form>
</div>
@endsection
