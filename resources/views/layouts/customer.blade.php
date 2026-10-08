<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Task Management') . ' — Client Portal')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --tm-bg: #f3f4f7;
            --tm-surface: #ffffff;
            --tm-surface-border: #e5e7ec;
            --tm-muted: #6b7280;
            --tm-accent: #4a9b3e;
            --tm-navy: #101b3d;
        }

        body {
            background-color: var(--tm-bg);
            color: #1f2430;
            min-height: 100vh;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .tm-serif {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        .tm-topbar {
            background: var(--tm-navy);
        }

        .tm-brand-logo {
            height: 26px;
            width: auto;
            display: block;
        }

        .tm-card {
            background: var(--tm-surface);
            border: 1px solid var(--tm-surface-border);
            border-radius: .85rem;
        }

        .tm-muted {
            color: var(--tm-muted);
        }

        .tm-divider-gold {
            border-bottom: 2px solid var(--tm-accent);
        }

        .tm-table {
            --bs-table-bg: transparent;
            --bs-table-color: #1f2430;
            --bs-table-border-color: var(--tm-surface-border);
            font-size: .875rem;
        }

        .tm-table thead th {
            color: var(--tm-muted);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
        }

        .tm-field {
            background: #fff;
            border: 1px solid var(--tm-surface-border);
            font-size: .875rem;
        }

        .tm-field:focus {
            border-color: var(--tm-accent);
            box-shadow: 0 0 0 .2rem rgba(74, 155, 62, .15);
        }

        .btn {
            font-size: .85rem;
        }

        .btn-tm-primary {
            background: var(--tm-navy);
            border-color: var(--tm-navy);
            color: #fff;
        }

        .btn-tm-primary:hover {
            background: #1a2a5c;
            border-color: #1a2a5c;
            color: #fff;
        }
    </style>

    @stack('styles')
</head>
<body>
    <header class="tm-topbar d-flex align-items-center justify-content-between px-4 py-2">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.firm_name', 'Firm') }}" class="tm-brand-logo">
            <div class="text-white small fw-semibold">Client Portal</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white small d-none d-sm-inline">{{ $customerName ?? 'Meera Traders' }}</span>
            <form method="POST" action="#">
                <button type="button" class="btn btn-outline-light btn-sm">Log out</button>
            </form>
        </div>
    </header>

    <main class="p-4">
        @yield('content')
    </main>
</body>
</html>
