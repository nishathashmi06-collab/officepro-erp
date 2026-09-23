<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<script>
    // Apply saved theme/sidebar state before first paint (no flash).
    (function () {
        var d = document.documentElement, s = null, sb = null;
        try { s = localStorage.getItem('op-theme'); sb = localStorage.getItem('op-sidebar'); } catch (e) {}
        var theme = s || @json(auth()->user()?->preference('theme')) || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        d.setAttribute('data-bs-theme', theme);
        if ((sb || @json(auth()->user()?->preference('sidebar'))) === 'collapsed') d.classList.add('op-sidebar-collapsed');
    })();
</script>
