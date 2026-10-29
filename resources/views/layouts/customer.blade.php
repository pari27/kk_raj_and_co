<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $pPageName = \Illuminate\Support\Str::beforeLast(trim($__env->yieldContent('title')), ' — ') ?: 'Client Portal'; @endphp
    <title>{{ config('app.firm_name') }} - {{ $pPageName }}</title>

    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="portal">
    @include('partials.flash-toast')
    @php
        $pClient = auth('customer')->user();
        $pInitial = mb_strtoupper(mb_substr(trim($pClient?->name ?? ''), 0, 1));
        $pUnread = $pClient ? \App\Models\ClientNotification::where('customer_id', $pClient->id)->whereNull('read_at')->count() : 0;
        $pPhone = config('app.firm_phone');
        $pNav = [
            ['route' => 'customer.dashboard', 'label' => 'Dashboard', 'short' => 'Home', 'icon' => 'home', 'match' => ['customer.dashboard']],
            ['route' => 'customer.tickets.index', 'label' => 'My services', 'short' => 'Services', 'icon' => 'list', 'match' => ['customer.tickets.*']],
            ['route' => 'customer.payments.index', 'label' => 'Payments', 'short' => 'Payments', 'icon' => 'credit-card', 'match' => ['customer.payments.*']],
            ['route' => 'customer.updates', 'label' => 'Updates', 'short' => 'Updates', 'icon' => 'bell', 'match' => ['customer.updates*'], 'badge' => $pUnread],
            ['route' => 'customer.profile', 'label' => 'Profile', 'short' => 'Profile', 'icon' => 'user', 'match' => ['customer.profile', 'customer.password.*']],
        ];
    @endphp

    <div class="d-flex flex-column flex-md-row flex-grow-1">
        <aside class="tm-sidebar pt-4 pb-3 d-none d-md-flex flex-column" id="pSidebar">
            <div class="px-3 mb-3">
                <a href="{{ route('customer.dashboard') }}"><img src="{{ config('app.firm_logo_url') }}" alt="{{ config('app.firm_name') }}" class="tm-brand-logo"></a>
            </div>

            <nav class="d-flex flex-column gap-1 px-2">
                @foreach ($pNav as $item)
                    <a href="{{ route($item['route']) }}" class="tm-nav-link {{ request()->routeIs(...$item['match']) ? 'active' : '' }}">
                        <span class="tm-nav-icon"><x-portal-icon :name="$item['icon']" :size="17" /></span>
                        {{ $item['label'] }}
                        @if (array_key_exists('badge', $item))<span class="badge rounded-pill bg-danger ms-auto u-87458d {{ $item['badge'] ? '' : 'd-none' }}" data-unread-badge>{{ $item['badge'] }}</span>@endif
                    </a>
                @endforeach
            </nav>

            @if ($pPhone)
                <div class="mt-auto px-3">
                    <div class="p-help">
                        <div class="p-help-label">Need help?</div>
                        <div class="fw-bold text-white">{{ $pPhone }}</div>
                    </div>
                </div>
            @endif
        </aside>

        <div class="flex-grow-1 min-vw-0 d-flex flex-column">
            <header class="tm-topbar d-flex align-items-center gap-3 px-3 px-md-4 py-2 sticky-top">
                <button class="tm-icon-btn d-none d-md-inline-flex" type="button" id="pSidebarToggle" aria-label="Show or hide the menu">
                    <x-portal-icon name="menu" :size="18" />
                </button>
                <a href="{{ route('customer.dashboard') }}" class="d-md-none"><img src="{{ config('app.firm_logo_url') }}" alt="{{ config('app.firm_name') }}" class="tm-brand-logo u-871e53"></a>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <a href="{{ route('customer.updates') }}" class="tm-icon-btn position-relative text-decoration-none u-e63c6b" aria-label="Updates">
                        <x-portal-icon name="bell" :size="17" />
                        <span id="tmBellBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger u-d95e57 {{ $pUnread ? '' : 'd-none' }}">{{ $pUnread }}</span>
                    </a>

                    <div class="dropdown">
                        <button class="btn d-flex align-items-center gap-2 p-0 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="tm-avatar">{{ $pInitial }}</div>
                            <div class="text-start d-none d-md-block">
                                <div class="small fw-semibold text-white">{{ $pClient?->name }}</div>
                                <div class="u-be643c">Client</div>
                            </div>
                            <svg class="d-none d-md-block" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="{{ route('customer.profile') }}" class="dropdown-item d-flex align-items-center gap-2 tm-dropdown-item">
                                    <span class="d-flex u-60cf33"><x-portal-icon name="user" :size="14" /></span> Profile
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('customer.password.edit') }}" class="dropdown-item d-flex align-items-center gap-2 tm-dropdown-item">
                                    <span class="d-flex u-60cf33"><x-portal-icon name="lock" :size="14" /></span> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 tm-dropdown-item"><x-portal-icon name="logout" :size="14" /> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="px-4 pb-5 pt-0 p-main flex-grow-1">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mt-3 mb-0" role="alert">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                @yield('content')
            </main>

            @include('partials.credit-footer')
        </div>
    </div>

    <nav class="pbottom d-md-none" aria-label="Main">
        @foreach ($pNav as $item)
            @php $active = request()->routeIs(...$item['match']); @endphp
            <a href="{{ route($item['route']) }}" class="{{ $active ? 'active' : '' }}">
                <span class="pbottom-icon">
                    <x-portal-icon :name="$item['icon']" :size="20" />
                    @if (array_key_exists('badge', $item))<span class="pbottom-dot {{ $item['badge'] ? '' : 'd-none' }}" data-unread-dot></span>@endif
                </span>
                <span>{{ $item['short'] }}</span>
            </a>
        @endforeach
    </nav>

    @include('partials.lock-screen', ['lockGuard' => 'customer'])
    @include('partials.session-watch')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('pSidebarToggle').addEventListener('click', function () {
            var side = document.getElementById('pSidebar');
            side.classList.toggle('d-md-flex');
            side.classList.toggle('d-md-none');
        });
    </script>
    @stack('scripts')
</body>
</html>
