<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Task Management'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --tm-bg: #0a1120;
            --tm-card: #101a2c;
            --tm-card-border: #1f2c42;
            --tm-field: #0d1526;
            --tm-field-border: #223247;
            --tm-muted: #8b98ac;
            --tm-accent: #4a9b3e;
        }

        body {
            background-color: var(--tm-bg);
            background-image:
                radial-gradient(circle at 50% 0%, rgba(74, 155, 62, .18), transparent 45%),
                radial-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px);
            background-size: auto, 22px 22px;
            min-height: 100vh;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .tm-serif {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        .tm-auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--tm-card);
            border: 1px solid var(--tm-card-border);
            border-radius: 1.25rem;
            padding: 2.25rem;
        }

        .tm-badge-icon {
            width: 48px;
            height: 48px;
            border-radius: .75rem;
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--tm-card-border);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tm-field-label {
            color: var(--tm-muted);
            font-size: .8rem;
            margin-bottom: .35rem;
        }

        .form-text {
            font-size: .78rem;
        }

        .tm-field {
            background: var(--tm-field);
            border: 1px solid var(--tm-field-border);
            color: #fff;
            font-size: .875rem;
        }

        .tm-field:-webkit-autofill,
        .tm-field:-webkit-autofill:hover,
        .tm-field:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff;
            -webkit-box-shadow: 0 0 0 1000px var(--tm-field) inset;
            box-shadow: 0 0 0 1000px var(--tm-field) inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        .tm-field:focus {
            background: var(--tm-field);
            border-color: var(--tm-accent);
            color: #fff;
            box-shadow: 0 0 0 .2rem rgba(74, 155, 62, .2);
        }

        .tm-field::placeholder {
            color: #56637a;
        }

        .btn {
            font-size: .85rem;
        }

        .tm-submit-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--tm-accent);
            border: 0;
            color: #05202e;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tm-muted {
            color: var(--tm-muted);
        }

        .tm-divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--tm-muted);
            font-size: .75rem;
        }

        .tm-divider::before,
        .tm-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--tm-card-border);
        }

        .tm-customer-panel {
            background: var(--tm-field);
            border: 1px solid var(--tm-field-border);
            border-radius: .85rem;
            padding: .9rem 1rem;
        }

        .tm-customer-panel:hover {
            border-color: var(--tm-accent);
        }

        .tm-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .06);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        a.tm-link {
            color: var(--tm-accent);
        }
    </style>

    @stack('styles')
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <main class="d-flex align-items-center justify-content-center w-100 px-3">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
