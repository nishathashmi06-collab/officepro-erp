@extends('layouts.guest')
@section('title', 'Forgot password')
@section('content')
    <div class="op-list-icon op-soft-primary mb-3" style="width:52px;height:52px;font-size:1.4rem"><i class="bi bi-key"></i></div>
    <h1 class="mb-1">Forgot your password?</h1>
    <p class="text-muted mb-4">Enter your email and we'll send you a secure link to choose a new one.</p>
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <button type="submit" class="btn btn-primary btn-lg w-100">Email reset link</button>
    </form>
    <p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
@endsection
