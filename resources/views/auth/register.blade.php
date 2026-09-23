@extends('layouts.guest')
@section('title', 'Create account')
@section('content')
    <h1 class="mb-1">Create your account</h1>
    <p class="text-muted mb-4">Your HR team will link it to your employee profile.</p>
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf
        <x-form.input name="name" label="Full name" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" label="Work email" required autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" help="At least 8 characters." />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Create account</button>
    </form>
    <p class="text-center text-muted small mt-4 mb-0">Already registered? <a href="{{ route('login') }}" class="fw-semibold">Sign in</a></p>
@endsection
