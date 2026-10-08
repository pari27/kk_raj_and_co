@extends('layouts.app')

@section('title', 'Change Password — ' . config('app.name', 'Task Management'))

@section('content')
@php
    $icons = [
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle>',
        'eye-off' => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68M6.61 6.61A13.53 13.53 0 0 0 1 12s4 7 11 7a10.94 10.94 0 0 0 5.11-1.27"></path><line x1="1" y1="1" x2="23" y2="23"></line>',
    ];
    $icon = fn (string $key, int $size = 14) => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
@endphp

<x-page-header title="Change password" subtitle="Keep your account safe with a strong password" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Change Password']]" />

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="tm-card p-0" style="overflow: hidden;">
            <div class="d-flex align-items-center gap-3 p-3" style="background: #101b3d;">
                <span class="d-flex align-items-center justify-content-center rounded-3" style="width: 38px; height: 38px; background: var(--tm-accent); color: #fff;">
                    {!! $icon('lock', 18) !!}
                </span>
                <div>
                    <h2 class="h6 tm-serif fw-bold mb-1 text-white">Update your password</h2>
                    <div class="small" style="color: rgba(255,255,255,.75);">Signed in as {{ auth()->user()->name }}</div>
                </div>
            </div>

            <div class="p-4">

                <form method="POST" action="{{ route('password.update') }}" id="changePasswordForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="tm-field-label d-block">Current password <span class="text-danger">*</span></label>
                                <div class="tm-password-field">
                                    {!! $icon('lock', 14) !!}
                                    <input type="password" name="current_password" id="currentPassword" class="form-control tm-field @error('current_password') is-invalid @enderror">
                                    <button type="button" class="tm-password-toggle" data-target="currentPassword">{!! $icon('eye', 15) !!}</button>
                                </div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-1">
                                <label class="tm-field-label d-block">New password <span class="text-danger">*</span></label>
                                <div class="tm-password-field">
                                    {!! $icon('lock', 14) !!}
                                    <input type="password" name="password" id="newPassword" class="form-control tm-field @error('password') is-invalid @enderror">
                                    <button type="button" class="tm-password-toggle" data-target="newPassword">{!! $icon('eye', 15) !!}</button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="tm-strength-track flex-grow-1">
                                    <span></span><span></span><span></span><span></span>
                                </div>
                                <div id="strengthLabel" class="small tm-muted" style="min-width: 32px;">&nbsp;</div>
                            </div>

                            <div class="mb-2">
                                <label class="tm-field-label d-block">Confirm new password <span class="text-danger">*</span></label>
                                <div class="tm-password-field">
                                    {!! $icon('lock', 14) !!}
                                    <input type="password" name="password_confirmation" id="confirmPassword" class="form-control tm-field">
                                    <button type="button" class="tm-password-toggle" data-target="confirmPassword">{!! $icon('eye', 15) !!}</button>
                                </div>
                            </div>
                            <div id="matchIndicator" class="small mb-3">&nbsp;</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="tm-card p-3 h-100" style="background: #f8f9fb;">
                                <h3 class="small fw-bold mb-3">Your new password needs</h3>
                                <ul class="list-unstyled small mb-0 tm-requirements" id="requirementsList">
                                    <li data-rule="length"><span class="tm-req-icon"></span> At least 8 characters</li>
                                    <li data-rule="upper"><span class="tm-req-icon"></span> One uppercase letter (A–Z)</li>
                                    <li data-rule="lower"><span class="tm-req-icon"></span> One lowercase letter (a–z)</li>
                                    <li data-rule="number"><span class="tm-req-icon"></span> One number (0–9)</li>
                                    <li data-rule="special"><span class="tm-req-icon"></span> One special character (! @ # $)</li>
                                </ul>
                                <div id="strengthHint" class="small mt-3 p-2 rounded d-none" style="background: #fdf3e0; color: #7a5b12;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-tm-primary d-flex align-items-center gap-2">
                            {!! $icon('lock', 14) !!} Update password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="tm-card p-0 mb-3" style="overflow: hidden;">
            <div class="p-2 px-3" style="background: #1f6b30;">
                <h3 class="small fw-bold text-white mb-0">Security tips</h3>
            </div>
            <ul class="mb-0 p-3 ps-4" style="line-height: 1.7; font-size: .8rem; color: #000;">
                <li>Use a short phrase you can remember, mixed with numbers and symbols</li>
                <li>Don't reuse a password from email or banking</li>
                <li>Never share your password with clients or other staff</li>
                <li>Change it straight away if you used a shared computer</li>
            </ul>
        </div>

        <div class="tm-card p-0" style="overflow: hidden;">
            <div class="p-2 px-3" style="background: #101b3d;">
                <h3 class="small fw-bold text-white mb-0">Recent logins</h3>
            </div>
            <div class="p-3">
                @forelse ($sessions as $session)
                    <div class="d-flex align-items-start justify-content-between mb-3 small">
                        <div>
                            <div class="fw-semibold">{{ $session['device'] }}</div>
                            <div style="font-size: .75rem; color: #000;">{{ $session['ip_address'] }} &middot; {{ $session['last_active']->diffForHumans() }}</div>
                        </div>
                        @if ($session['is_current_device'])
                            <span class="badge rounded-pill" style="background: #1f6b30; color: #fff;">This device</span>
                        @endif
                    </div>
                @empty
                    <p class="small tm-muted mb-0">No session activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1f6b30" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        var crossIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
        var eyeIcon = document.querySelector('.tm-password-toggle').innerHTML;
        var eyeOffIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68M6.61 6.61A13.53 13.53 0 0 0 1 12s4 7 11 7a10.94 10.94 0 0 0 5.11-1.27"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

        var currentPassword = document.getElementById('currentPassword');
        var newPassword = document.getElementById('newPassword');
        var confirmPassword = document.getElementById('confirmPassword');
        var strengthSegments = document.querySelectorAll('.tm-strength-track span');
        var strengthLabel = document.getElementById('strengthLabel');
        var strengthHint = document.getElementById('strengthHint');
        var matchIndicator = document.getElementById('matchIndicator');
        var requirementItems = document.querySelectorAll('#requirementsList li');

        document.querySelectorAll('.tm-password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.getAttribute('data-target'));
                var isHidden = target.type === 'password';
                target.type = isHidden ? 'text' : 'password';
                button.innerHTML = isHidden ? eyeOffIcon : eyeIcon;
            });
        });

        function evaluateRules(value) {
            return {
                length: value.length >= 8,
                upper: /[A-Z]/.test(value),
                lower: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                special: /[^A-Za-z0-9]/.test(value),
            };
        }

        function updateChecklist(rules) {
            requirementItems.forEach(function (item) {
                var rule = item.getAttribute('data-rule');
                var icon = item.querySelector('.tm-req-icon');
                var met = !!rules[rule];
                icon.innerHTML = met ? checkIcon : crossIcon;
                item.classList.toggle('tm-req-met', met);
            });
        }

        function updateStrength(rules) {
            var metCount = Object.values(rules).filter(Boolean).length;
            var filled = metCount <= 1 ? 1 : (metCount <= 3 ? 2 : (metCount === 4 ? 3 : 4));
            var color = '#dc3545';
            var label = 'Weak';

            if (metCount === 5) {
                color = '#1f6b30';
                label = 'Strong';
            } else if (metCount === 4) {
                color = '#7cb342';
                label = 'Good';
            } else if (metCount >= 2) {
                color = '#e0a800';
                label = 'Fair';
            }

            strengthSegments.forEach(function (segment, index) {
                segment.style.background = (newPassword.value && index < filled) ? color : '#e9ecef';
            });
            strengthLabel.textContent = newPassword.value ? label : ' ';

            var missing = [];
            if (!rules.number) missing.push('a number');
            if (!rules.special) missing.push('a special character');

            if (newPassword.value && missing.length) {
                strengthHint.textContent = 'Add ' + missing.join(' and ') + ' to make it strong.';
                strengthHint.classList.remove('d-none');
            } else {
                strengthHint.classList.add('d-none');
            }
        }

        function updateMatch() {
            if (!confirmPassword.value) {
                matchIndicator.innerHTML = '&nbsp;';
                return;
            }

            if (confirmPassword.value === newPassword.value) {
                matchIndicator.innerHTML = '<span style="color:#1f6b30;">' + checkIcon + ' Passwords match</span>';
            } else {
                matchIndicator.innerHTML = '<span style="color:#dc3545;">' + crossIcon + ' Passwords do not match</span>';
            }
        }

        function refresh() {
            var rules = evaluateRules(newPassword.value);
            updateChecklist(rules);
            updateStrength(rules);
            updateMatch();
        }

        [currentPassword, newPassword, confirmPassword].forEach(function (input) {
            input.addEventListener('input', refresh);
        });

        refresh();
    })();
</script>
@endpush
@endsection
