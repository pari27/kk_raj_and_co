{{-- Every few seconds, ask the server whether this account has been signed in somewhere else, and how many notifications are unread. --}}
<script>
    (function () {
        var url = @json(route('session.ping'));
        var lockKey = 'tmLockedUser';

        // Keeps the bell's red number in step with what has been read, without reloading the page.
        function showUnread(count) {
            var badge = document.getElementById('tmBellBadge');
            var inMenu = document.getElementById('tmBellNew');
            var navBadges = document.querySelectorAll('[data-unread-badge]');
            var navDots = document.querySelectorAll('[data-unread-dot]');

            if (typeof count !== 'number') { return; }

            if (badge) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.toggle('d-none', count === 0);
            }

            if (inMenu) {
                inMenu.textContent = count + ' new';
                inMenu.classList.toggle('d-none', count === 0);
            }

            // The client portal's menu badge and phone dot.
            navBadges.forEach(function (el) {
                el.textContent = count;
                el.classList.toggle('d-none', count === 0);
            });
            navDots.forEach(function (el) { el.classList.toggle('d-none', count === 0); });
        }

        function check() {
            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status !== 401) {
                        showUnread(result.data.unread);
                        return;
                    }

                    if (!result.data || !result.data.replaced) { return; }
                    try { localStorage.removeItem(lockKey); } catch (e) {}
                    window.location.href = result.data.login_url;
                })
                .catch(function () {});
        }

        window.tmCheckNow = check;

        setInterval(check, 20000);

        // Coming back to this tab or page (for example after opening a notification elsewhere) refreshes it at once.
        window.addEventListener('focus', check);
        window.addEventListener('pageshow', function (event) { if (event.persisted) { check(); } });
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { check(); } });
    })();
</script>
