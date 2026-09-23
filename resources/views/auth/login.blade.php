@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
    <h1 class="mb-1">Welcome back 👋</h1>
    <p class="text-muted mb-4">Sign in to your OfficePro workspace.</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" placeholder="you@company.com" />
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="f_password" class="form-label">Password<span class="req">*</span></label>
                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
            </div>
            <input type="password" name="password" id="f_password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="••••••••">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Keep me signed in</label>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Sign in</button>
    </form>

    @if (setting('registration_enabled') == '1')
        <p class="text-center text-muted small mt-4 mb-0">New here? <a href="{{ route('register') }}" class="fw-semibold">Create an account</a></p>
    @endif

    @if (app()->environment('local', 'testing') || config('app.show_demo_accounts'))
        <div class="op-card mt-4 p-3 op-demo-accounts">
            <div class="small-caps mb-2"><i class="bi bi-info-circle"></i> Demo accounts (seed data) — password: <code>password</code></div>
            <div class="d-flex flex-wrap gap-2">
                @foreach (['superadmin@officepro.test' => 'Super Admin', 'admin@officepro.test' => 'Admin', 'hr@officepro.test' => 'HR Manager', 'manager@officepro.test' => 'Manager', 'employee@officepro.test' => 'Employee'] as $email => $role)
                    <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('f_email').value='{{ $email }}';document.getElementById('f_password').value='password';">{{ $role }}</button>
                @endforeach
            </div>
        </div>
    @endif
@endsection
