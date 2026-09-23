<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    @include('partials.head')
    <title>@hasSection('title')@yield('title') · @endif{{ setting('company_name') }} · OfficePro</title>
    @stack('styles')
</head>
@php($authUser = auth()->user())
<body data-preferences-url="{{ route('profile.preferences') }}">
<div class="op-app">
    @include('partials.sidebar')
    <div class="op-backdrop"></div>

    <div class="op-main">
        @include('partials.topbar')

        <main class="op-content" id="main">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="op-footer">
            <span>&copy; {{ date('Y') }} {{ setting('company_name') }} · Powered by <strong>OfficePro</strong></span>
            <span>{{ now()->format('l, '.setting('date_format', 'M d, Y')) }} · {{ config('app.timezone') }}</span>
        </footer>
    </div>
</div>

{{-- Shared confirmation dialog used by forms with data-confirm --}}
<div class="modal fade" id="op-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="op-confirm-icon op-soft-danger mx-auto"><i class="bi bi-exclamation-triangle"></i></div>
                <h5 class="mb-2" data-confirm-title>Are you sure?</h5>
                <p class="text-muted small mb-4" data-confirm-message></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger flex-fill" data-confirm-accept>Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts-before')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
