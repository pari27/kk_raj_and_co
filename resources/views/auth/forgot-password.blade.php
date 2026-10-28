@extends('layouts.guest')

@section('title', 'Forgot password — ' . config('app.name', 'Task Management'))

@section('content')
<div class="tm-auth-card">
    <div class="text-center mb-4">
        <img src="{{ config('app.firm_logo_url') }}" alt="{{ config('app.firm_name') }}" class="d-block mx-auto mb-3 u-a60e1d">
        <h1 class="h4 text-white fw-semibold mb-1">Forgot password?</h1>
        <p class="tm-muted small mb-0">
            We'll email a one-time code to reset your password.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small" role="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.verify') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control tm-field" placeholder="you@yourfirm.com" required autofocus autocomplete="username">
                <button type="submit" formaction="{{ route('password.email') }}" class="btn btn-outline-secondary flex-shrink-0">Send me a code</button>
            </div>
        </div>

        <div class="mb-4">
            <label for="code" class="tm-field-label d-block">6-digit code <span class="text-danger">*</span></label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-control tm-field text-center u-1a58d1" placeholder="••••••">
            @if (session('otp_expires_at'))
                <div class="form-text tm-muted" id="otp-timer" data-expires-at="{{ session('otp_expires_at') }}">
                    The code expires in <strong id="otp-remaining"></strong> and works once.
                </div>
            @else
                <div class="form-text tm-muted">The code expires in {{ \App\Services\ClientOtpService::EXPIRES_IN_MINUTES }} minutes and works once.</div>
            @endif
        </div>

        <button type="submit" class="btn btn-tm-primary w-100 mb-3">Verify code</button>

        <p class="text-center tm-muted small mb-0">
            <a href="{{ route('login') }}" class="tm-link text-decoration-underline">Back to login</a>
        </p>
    </form>
</div>

@if (session('otp_expires_at'))
<script>
    (function () {
        var timer = document.getElementById('otp-timer');
        var label = document.getElementById('otp-remaining');
        var expiresAt = parseInt(timer.dataset.expiresAt, 10) * 1000;
        // Use the gap between the server's time and this device's clock so a wrong PC clock doesn't skew it.
        var offset = {{ now()->timestamp }} * 1000 - Date.now();

        function tick() {
            var left = Math.max(0, Math.round((expiresAt - (Date.now() + offset)) / 1000));
            if (left === 0) {
                timer.textContent = 'The code has expired. Please request a new one.';
                clearInterval(handle);
                return;
            }
            label.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
        }

        var handle = setInterval(tick, 1000);
        tick();
    })();
</script>
@endif
@endsection
