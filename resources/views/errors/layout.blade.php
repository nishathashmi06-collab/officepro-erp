<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') · OfficePro</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>try { var t = localStorage.getItem('op-theme'); if (t) document.documentElement.setAttribute('data-bs-theme', t); } catch (e) {}</script>
</head>
<body>
<div class="op-error">
    <div class="op-animate" style="max-width: 480px">
        <div class="op-list-icon op-soft-primary mx-auto mb-3" style="width:64px;height:64px;font-size:1.7rem"><i class="bi @yield('icon', 'bi-exclamation-circle')"></i></div>
        <div class="code">@yield('code')</div>
        <h1 class="h3 mt-2">@yield('title')</h1>
        <p class="text-muted mb-4">@yield('message')</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Go back</a>
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
