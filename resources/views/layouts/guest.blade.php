<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $gPageName = \Illuminate\Support\Str::beforeLast(trim($__env->yieldContent('title')), ' — ') ?: 'Sign in'; @endphp
    <title>{{ config('app.firm_name') }} - {{ $gPageName }}</title>

    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/guest.css') }}" rel="stylesheet">


    @stack('styles')
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <main class="d-flex align-items-center justify-content-center w-100 px-3">
        @yield('content')
    </main>

    @include('partials.credit-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
