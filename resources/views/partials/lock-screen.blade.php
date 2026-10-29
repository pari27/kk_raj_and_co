@php
    // Staff pages use the default guard; the client portal passes $lockGuard = 'customer'.
    $lockGuard = $lockGuard ?? 'web';
    $lockClient = $lockGuard === 'customer';
    $lockUser = $lockClient ? auth('customer')->user() : auth()->user();
    $lockIdleMinutes = max(1, (int) config('session.lock_after_minutes'));
@endphp

{{-- Covers the page after 15 idle minutes until the person types their password. --}}
<div class="tm-lock-overlay" id="tmLock" role="dialog" aria-modal="true" aria-labelledby="tmLockTitle"
     data-user="{{ $lockGuard }}:{{ $lockUser->id }}:{{ session('login_token_'.$lockGuard) }}"
     data-idle-minutes="{{ $lockIdleMinutes }}"
     data-unlock-url="{{ route($lockClient ? 'customer.lock.unlock' : 'lock.unlock') }}"
     data-logout-url="{{ route($lockClient ? 'customer.logout' : 'logout') }}"
     data-login-url="{{ route($lockClient ? 'customer.login' : 'login') }}"
     data-forgot-url="{{ $lockClient ? route('customer.verify-otp') : route('password.request') }}"
     data-token="{{ csrf_token() }}">
    <div class="tm-lock-card">
        <div class="tm-lock-top">
            <div class="tm-lock-time"><span id="tmLockClock"></span> <small id="tmLockAmPm"></small></div>
            <div class="tm-lock-date" id="tmLockDate"></div>
            <div class="tm-lock-pill">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Locked after {{ $lockIdleMinutes }} {{ \Illuminate\Support\Str::plural('minute', $lockIdleMinutes) }} of no activity
            </div>
        </div>

        <div class="tm-lock-body">
            <div class="tm-lock-avatar">{{ strtoupper(substr($lockUser->name, 0, 1)) }}</div>
            <h2 class="tm-lock-title" id="tmLockTitle">Welcome back, {{ $lockUser->name }}</h2>
            <div class="tm-lock-email">{{ $lockUser->email }}</div>

            <form id="tmLockForm" novalidate>
                <label for="tmLockPassword" class="tm-lock-label">Enter your password to continue</label>
                <div class="tm-lock-input">
                    <svg class="tm-lock-input-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" id="tmLockPassword" autocomplete="current-password" required>
                    <button type="button" class="tm-lock-eye" id="tmLockEye" aria-label="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <div class="tm-lock-error" id="tmLockError" role="alert"></div>

                <button type="submit" class="tm-lock-submit" id="tmLockSubmit">
                    Unlock
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </button>
            </form>

            <div class="tm-lock-links">
                <a href="{{ $lockClient ? route('customer.verify-otp') : route('password.request') }}" id="tmLockForgot">Forgot password?</a>
                <a href="{{ route($lockClient ? 'customer.logout' : 'logout') }}" id="tmLockLogout" class="tm-lock-logout">Not you? Log out</a>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var overlay = document.getElementById('tmLock');
        var userId = overlay.dataset.user;
        var idleMs = parseInt(overlay.dataset.idleMinutes, 10) * 60 * 1000;
        var lockKey = 'tmLockedUser';
        var activityKey = 'tmLastActivity';
        var form = document.getElementById('tmLockForm');
        var input = document.getElementById('tmLockPassword');
        var errorBox = document.getElementById('tmLockError');
        var submit = document.getElementById('tmLockSubmit');
        var clockTimer = null;
        var lastWrite = 0;

        function read(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
        function write(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }
        function remove(key) { try { localStorage.removeItem(key); } catch (e) {} }

        function isLocked() { return overlay.classList.contains('is-open'); }

        function tickClock() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('tmLockClock').textContent = ((hours % 12) || 12) + ':' + minutes;
            document.getElementById('tmLockAmPm').textContent = hours < 12 ? 'AM' : 'PM';
            document.getElementById('tmLockDate').textContent =
                now.toLocaleDateString('en-GB', { weekday: 'long' }) + ', ' +
                now.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        function lock() {
            if (isLocked()) { return; }
            write(lockKey, userId);
            show();
        }

        function show() {
            overlay.classList.add('is-open');
            document.body.classList.add('tm-locked');
            tickClock();
            clockTimer = setInterval(tickClock, 1000);
            setTimeout(function () { input.focus(); }, 50);
        }

        function hide() {
            overlay.classList.remove('is-open');
            document.body.classList.remove('tm-locked');
            clearInterval(clockTimer);
            input.value = '';
            errorBox.textContent = '';
            remove(lockKey);
            write(activityKey, String(Date.now()));
        }

        function activity() {
            if (isLocked()) { return; }
            var now = Date.now();
            if (now - lastWrite < 1000) { return; }
            lastWrite = now;
            write(activityKey, String(now));
        }

        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (name) {
            document.addEventListener(name, activity, { passive: true, capture: true });
        });

        // Another tab locked or unlocked: follow it.
        window.addEventListener('storage', function (e) {
            if (e.key !== lockKey) { return; }
            if (e.newValue === userId && !isLocked()) { show(); }
            if (e.newValue === null && isLocked()) { hide(); }
        });

        setInterval(function () {
            var last = parseInt(read(activityKey) || '0', 10);
            if (!isLocked() && Date.now() - last >= idleMs) { lock(); }
        }, 5000);

        // A page load counts as activity; a lock left by this same person stays after a refresh.
        var stored = read(lockKey);
        if (stored === userId) {
            show();
        } else {
            remove(lockKey);
            write(activityKey, String(Date.now()));
        }

        function post(url) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': overlay.dataset.token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: input.value })
            });
        }

        function leave(url) {
            remove(lockKey);
            window.location.href = url;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errorBox.textContent = '';

            if (input.value === '') {
                errorBox.textContent = 'Please enter your password.';
                return;
            }

            submit.disabled = true;

            post(overlay.dataset.unlockUrl).then(function (response) {
                if (response.status === 401 || response.status === 419) {
                    leave(overlay.dataset.loginUrl);
                    return null;
                }
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            }).then(function (result) {
                if (!result) { return; }
                if (result.ok) { hide(); return; }
                errorBox.textContent = result.data.message || 'That password is not right. Please try again.';
                input.select();
            }).catch(function () {
                errorBox.textContent = 'Could not reach the server. Please try again.';
            }).then(function () {
                submit.disabled = false;
            });
        });

        document.getElementById('tmLockEye').addEventListener('click', function () {
            input.type = input.type === 'password' ? 'text' : 'password';
        });

        function signOut(next) {
            fetch(overlay.dataset.logoutUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': overlay.dataset.token, 'Accept': 'application/json' }
            }).catch(function () {}).then(function () { leave(next); });
        }

        document.getElementById('tmLockLogout').addEventListener('click', function (e) {
            e.preventDefault();
            signOut(overlay.dataset.loginUrl);
        });

        // The forgot-password page is only for signed-out visitors, so sign out first.
        document.getElementById('tmLockForgot').addEventListener('click', function (e) {
            e.preventDefault();
            signOut(overlay.dataset.forgotUrl);
        });
    })();
</script>
