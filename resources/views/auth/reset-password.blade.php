@extends('layouts.guest')
@section('title', 'Reset password')
@section('content')
    <h1 class="mb-1">Choose a new password</h1>
    <p class="text-muted mb-4">Make it strong — at least 8 characters.</p>
    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" :value="$email" required autocomplete="username" />
        <x-form.input name="password" type="password" label="New password" required autofocus autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary btn-lg w-100">Reset password</button>
    </form>
@endsection
