<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    @include('partials.head')
    <title>@yield('title', 'Sign in') · OfficePro</title>
</head>
<body>
<div class="op-auth">
    <aside class="op-auth-aside">
        <a href="{{ route('login') }}" class="d-flex align-items-center gap-2 text-white fw-bold fs-5">
            <span class="op-brand-logo"><i class="bi bi-briefcase-fill"></i></span> OfficePro
        </a>
        <div class="op-animate">
            <h2 class="mb-3">Run your entire office from one elegant workspace.</h2>
            <p class="mb-4">People, attendance, leave, payroll, expenses, assets and documents — connected, secure and always up to date.</p>
            <div class="op-auth-feature"><i class="bi bi-people"></i> Employee records & self-service portal</div>
            <div class="op-auth-feature"><i class="bi bi-fingerprint"></i> One-click attendance with automatic hours</div>
            <div class="op-auth-feature"><i class="bi bi-cash-stack"></i> Payroll with printable PDF salary slips</div>
            <div class="op-auth-feature"><i class="bi bi-shield-check"></i> Role-based access for every team member</div>
        </div>
        <div class="small text-white-50">&copy; {{ date('Y') }} {{ setting('company_name') }}</div>
    </aside>
    <main class="op-auth-main">
        <div class="op-auth-card op-animate">
            <div class="d-lg-none mb-4 d-flex align-items-center gap-2 fw-bold fs-5">
                <span class="op-brand-logo"><i class="bi bi-briefcase-fill"></i></span> OfficePro
            </div>
            @php($hideErrorSummary = true)
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
