@extends('layouts.app')
@section('title', 'Account & Preferences')
@section('content')
<x-page-header title="Account & Preferences" subtitle="Your login details, password, appearance and notifications." :breadcrumbs="['Account' => null]">
    <x-slot:actions>@if ($user->employee)<a href="{{ route('employees.show', $user->employee) }}" class="btn btn-light"><i class="bi bi-person"></i> View my employee profile</a>@endif</x-slot:actions>
</x-page-header>
@unless ($user->employee)
    <x-alert type="info" :dismissible="false">Your account is not linked to an employee profile yet, so attendance and leave self-service are unavailable. Please contact HR.</x-alert>
@endunless
<div class="row g-4">
    <div class="col-xl-6">
        <x-card title="Profile" icon="bi-person-circle" class="mb-4">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf @method('PUT')
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$user->name" :src="$user->photo_url" size="64" />
                    <div><div class="fw-semibold">{{ $user->role?->name }}</div><div class="small text-muted">Last login {{ $user->last_login_at?->diffForHumans() ?? '—' }}</div></div>
                </div>
                <x-form.input name="name" label="Full name" :value="$user->name" required />
                <x-form.input name="email" type="email" label="Email" :value="$user->email" required />
                <x-form.input name="phone" label="Phone" :value="$user->phone" />
                <x-form.input name="profile_photo" type="file" label="Profile photo" accept="image/jpeg,image/png,image/webp" />
                <div class="text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save profile</button></div>
            </form>
        </x-card>
        <x-card title="Change password" icon="bi-key">
            <form method="POST" action="{{ route('profile.password') }}" novalidate>
                @csrf @method('PUT')
                <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" help="At least 8 characters." />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                <div class="text-end"><button class="btn btn-primary"><i class="bi bi-shield-lock"></i> Update password</button></div>
            </form>
        </x-card>
    </div>
    <div class="col-xl-6">
        <x-card title="Appearance" icon="bi-palette" class="mb-4" id="appearance">
            <form method="POST" action="{{ route('profile.preferences') }}">
                @csrf @method('PUT')
                <input type="hidden" name="section" value="appearance">
                <label class="form-label">Theme</label>
                <div class="d-flex gap-2 mb-3">
                    @foreach (['light' => ['Light', 'bi-sun'], 'dark' => ['Dark', 'bi-moon-stars']] as $val => [$lbl, $icon])
                        <input type="radio" class="btn-check" name="theme" id="theme-{{ $val }}" value="{{ $val }}" @checked($user->preference('theme', 'light') === $val) onchange="document.documentElement.setAttribute('data-bs-theme', this.value); try{localStorage.setItem('op-theme', this.value)}catch(e){}; document.dispatchEvent(new CustomEvent('op:theme'))">
                        <label class="btn btn-light flex-fill py-3" for="theme-{{ $val }}"><i class="bi {{ $icon }} fs-5"></i> {{ $lbl }}</label>
                    @endforeach
                </div>
                <label class="form-label">Sidebar (desktop)</label>
                <div class="d-flex gap-2 mb-3">
                    @foreach (['expanded' => 'Expanded', 'collapsed' => 'Collapsed (icons only)'] as $val => $lbl)
                        <input type="radio" class="btn-check" name="sidebar" id="sb-{{ $val }}" value="{{ $val }}" @checked($user->preference('sidebar', 'expanded') === $val) onchange="document.documentElement.classList.toggle('op-sidebar-collapsed', this.value === 'collapsed'); try{localStorage.setItem('op-sidebar', this.value)}catch(e){}">
                        <label class="btn btn-light flex-fill" for="sb-{{ $val }}">{{ $lbl }}</label>
                    @endforeach
                </div>
                <div class="text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save appearance</button></div>
            </form>
        </x-card>
        <x-card title="E-mail notifications" icon="bi-envelope" id="notifications" subtitle="In-app notifications are always on. Choose which ones are also e-mailed.">
            <form method="POST" action="{{ route('profile.preferences') }}">
                @csrf @method('PUT')
                <input type="hidden" name="section" value="notifications">
                @foreach ($notificationTypes as $key => $lbl)
                    <x-form.checkbox :name="'email_notifications['.$key.']'" :label="$lbl" :checked="(bool) $user->preference('email_notifications.'.$key, false)" class="mb-2" />
                @endforeach
                <div class="text-end mt-3"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save notification preferences</button></div>
            </form>
        </x-card>
    </div>
</div>
@endsection
