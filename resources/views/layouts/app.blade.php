<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Task Management'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
    @include('partials.flash-toast')
    @php
        $tmUser = auth()->user();
        $tmIsAdminLike = $tmUser->isSuperAdmin() || $tmUser->isAdmin();
        $tmIcons = [
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect>',
            'plus-circle' => '<circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line>',
            'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"></path>',
            'tag' => '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"></path><circle cx="7" cy="7" r="1.4"></circle>',
            'credit-card' => '<rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>',
            'bar-chart' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
            'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
            'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
            'users' => '<circle cx="9" cy="7" r="4"></circle><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path><path d="M17 3.13a4 4 0 0 1 0 7.75"></path><path d="M21 21v-2a4 4 0 0 0-3-3.85"></path>',
            'award' => '<circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>',
            'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
            'sliders' => '<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>',
            'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
            'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
            'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        ];
        $tmIcon = fn (string $key, int $size = 17) => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$tmIcons[$key].'</svg>';
    @endphp
    <div class="d-flex flex-column flex-md-row">
        <aside class="tm-sidebar pt-4 pb-3">
            <div class="px-3 mb-2 d-flex align-items-center justify-content-between gap-2">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.firm_name', 'Firm') }}" class="tm-brand-logo">
                </a>
                <button class="btn btn-sm btn-outline-light tm-menu-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#tmSidebarNav" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>

            <nav class="collapse d-md-block" id="tmSidebarNav">
                <div class="d-flex flex-column gap-1 px-2">
                    <a href="{{ route('dashboard') }}" class="tm-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('grid') !!}</span> Dashboard
                    </a>
                    <a href="{{ route('enquiries.index') }}" class="tm-nav-link {{ request()->routeIs('enquiries.*') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('file-text') !!}</span> Enquiries
                    </a>
                    <a href="{{ route('customers.index') }}" class="tm-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('user') !!}</span> Clients
                    </a>
                    <a href="{{ route('tickets.index') }}" class="tm-nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('tag') !!}</span> Tickets
                    </a>
                    <a href="{{ route('payments.index') }}" class="tm-nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('credit-card') !!}</span> Payments
                    </a>
                    <a href="{{ route('reports.index') }}" class="tm-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <span class="tm-nav-icon">{!! $tmIcon('bar-chart') !!}</span> Reports
                    </a>

                    @if ($tmIsAdminLike)
                        <div class="tm-nav-section-label">Masters</div>
                        <a href="{{ route('admin.services.index') }}" class="tm-nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('briefcase') !!}</span> Master Services
                        </a>
                        <a href="{{ route('admin.employees.index') }}" class="tm-nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('users') !!}</span> Master Employees
                        </a>
                        <a href="{{ route('admin.designations.index') }}" class="tm-nav-link {{ request()->routeIs('admin.designations.*') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('award') !!}</span> Master Designations
                        </a>

                        <div class="tm-nav-section-label">System</div>
                        <a href="{{ route('admin.admin-accounts.index') }}" class="tm-nav-link {{ request()->routeIs('admin.admin-accounts.*') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('shield') !!}</span> Admin Accounts
                        </a>
                        <a href="{{ route('admin.audit-log') }}" class="tm-nav-link {{ request()->routeIs('admin.audit-log') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('clock') !!}</span> Audit Log
                        </a>
                        <a href="{{ route('admin.settings.firm-profile') }}" class="tm-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <span class="tm-nav-icon">{!! $tmIcon('sliders') !!}</span> Settings
                        </a>
                    @endif
                </div>
            </nav>
        </aside>

        <div class="flex-grow-1 min-vw-0">
            <header class="tm-topbar d-flex align-items-center gap-3 px-4 py-2 sticky-top">
                <button class="tm-icon-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#tmSidebarNav">
                    {!! $tmIcon('grid', 18) !!}
                </button>

                <div class="tm-search d-none d-md-flex align-items-center gap-2 px-3 py-2 flex-grow-1" style="max-width: 420px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search ticket no., client name, phone or email" disabled>
                </div>

                <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="dropdown">
                    <button class="btn btn-sm rounded-pill d-flex align-items-center gap-1 text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: var(--tm-accent); border: 1px solid var(--tm-accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        FY {{ now()->month >= 4 ? now()->year : now()->year - 1 }}-{{ substr((string) (now()->month >= 4 ? now()->year + 1 : now()->year), -2) }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item disabled">Other financial years — coming soon</span></li>
                    </ul>
                </div>

                <button class="tm-icon-btn position-relative" type="button" title="Notifications" style="width: 31px; height: 31px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .55rem;">5</span>
                </button>

                <div class="dropdown">
                    <button class="btn d-flex align-items-center gap-2 p-0 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="tm-avatar">{{ strtoupper(substr($tmUser->name, 0, 1)) }}</div>
                        <div class="text-start d-none d-sm-block">
                            <div class="small fw-semibold text-white">{{ $tmUser->name }}</div>
                            <div style="font-size: .72rem; color: rgba(255,255,255,.6);">{{ $tmUser->role->label() }}</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9aa1b0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="{{ route('password.change') }}" class="dropdown-item d-flex align-items-center gap-2 tm-dropdown-item">
                                <span class="d-flex" style="color: var(--tm-accent);">{!! $tmIcon('lock', 14) !!}</span> Change Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 tm-dropdown-item">
                                    {!! $tmIcon('log-out', 14) !!} Log out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                </div>
            </header>

            <main class="px-4 pb-4 pt-0">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('input', function (e) {
            if (!e.target.classList || !e.target.classList.contains('js-price-input')) {
                return;
            }

            var value = e.target.value.trim();
            var valid = value === '' || /^\d+(\.\d{1,2})?$/.test(value);
            e.target.classList.toggle('is-invalid', !valid);
        });
    </script>
    @stack('scripts')
</body>
</html>
