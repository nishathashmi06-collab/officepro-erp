@extends('layouts.app')
@php($editing = $user->exists)
@section('title', $editing ? 'Edit user' : 'Add user')
@section('content')
<x-page-header :title="$editing ? 'Edit '.$user->name : 'Add user'" :breadcrumbs="['Users' => route('users.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-7">
<x-card>
    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" enctype="multipart/form-data" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row">
            <x-form.input class="col-md-6" name="name" label="Full name" :value="$user->name" required />
            <x-form.input class="col-md-6" name="email" type="email" label="Email" :value="$user->email" required />
            <x-form.input class="col-md-6" name="phone" label="Phone" :value="$user->phone" />
            <x-form.select class="col-md-6" name="role_id" label="Role" :options="$roles->pluck('name', 'id')" :value="$user->role_id" placeholder="Select role…" required />
            <x-form.select class="col-md-6" name="status" label="Status" :options="['active' => 'Active', 'disabled' => 'Disabled']" :value="$user->status" required />
            <x-form.input class="col-md-6" name="profile_photo" type="file" label="Photo" accept="image/*" />
            <x-form.input class="col-md-6" name="password" type="password" :label="$editing ? 'New password' : 'Password'" :required="! $editing" autocomplete="new-password" :help="$editing ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.'" />
            <x-form.input class="col-md-6" name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" />
        </div>
        @unless (auth()->user()->isSuperAdmin())<p class="small text-muted"><i class="bi bi-info-circle"></i> Only a super admin can assign the Admin or Super Admin roles.</p>@endunless
        <x-form.actions :cancel="route('users.index')" />
    </form>
</x-card>
</div></div>
@endsection
