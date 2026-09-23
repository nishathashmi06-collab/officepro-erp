@extends('layouts.app')
@section('title', $account->name)
@section('content')
<x-page-header :title="$account->name" :subtitle="$account->email" :breadcrumbs="['Users' => route('users.index'), $account->name => null]">
    <x-slot:actions>
        @can('update', $account)
            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#pw-modal"><i class="bi bi-key"></i> Reset password</button>
            <a href="{{ route('users.edit', $account) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>
<div class="row g-4">
    <div class="col-xl-5">
        <x-card>
            <div class="text-center mb-4">
                <x-avatar :name="$account->name" :src="$account->photo_url" size="84" />
                <h2 class="h5 mt-3 mb-1">{{ $account->name }} @if($account->is_primary)<i class="bi bi-patch-check-fill text-primary" title="Primary super admin"></i>@endif</h2>
                <span class="op-badge op-soft-purple no-dot">{{ $account->role?->name }}</span> <x-status-badge :status="$account->status" />
            </div>
            <dl class="op-dl">
                <dt>Email</dt><dd>{{ $account->email }}</dd>
                <dt>Phone</dt><dd>{{ $account->phone ?? '—' }}</dd>
                <dt>Employee</dt><dd>@if($account->employee)<a href="{{ route('employees.show', $account->employee) }}">{{ $account->employee->full_name }} ({{ $account->employee->employee_code }})</a>@else Not linked @endif</dd>
                <dt>Last login</dt><dd>{{ $account->last_login_at?->format('M d, Y h:i A') ?? 'Never' }} @if($account->last_login_ip)<span class="text-muted small">· {{ $account->last_login_ip }}</span>@endif</dd>
                <dt>Created</dt><dd>{{ $account->created_at->format('M d, Y') }}</dd>
            </dl>
        </x-card>
    </div>
    <div class="col-xl-7">
        <x-card title="Recent activity" icon="bi-activity">
            @if ($activity->isEmpty())
                <p class="text-muted small mb-0">No activity yet.</p>
            @else
                <div class="op-timeline">
                    @foreach ($activity as $log)
                        <div class="op-timeline-item"><span class="dot"><i class="bi {{ $log->icon() }}"></i></span><div class="text">{{ $log->description }}</div><time>{{ $log->created_at->format('M d, Y h:i A') }}</time></div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@can('update', $account)
    <x-modal id="pw-modal" title="Reset password" icon="bi-key">
        <form method="POST" action="{{ route('users.password', $account) }}">
            @csrf @method('PUT')
            <div class="modal-body">
                <x-form.input name="new_password" type="password" label="New password" required autocomplete="new-password" />
                <x-form.input name="new_password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Reset password</button></div>
        </form>
    </x-modal>
@endcan
@endsection
