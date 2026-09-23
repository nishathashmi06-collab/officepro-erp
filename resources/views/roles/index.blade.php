@extends('layouts.app')
@section('title', 'Roles & Permissions')
@section('content')
<x-page-header title="Roles & Permissions" subtitle="Control what each role can access. Changes apply immediately and are enforced on the server." :breadcrumbs="['Roles & permissions' => null]" />
<ul class="nav op-tabs mb-4" role="tablist">
    @foreach ($roles as $role)
        <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#pane-role-{{ $role->id }}" type="button"><i class="bi bi-shield"></i> {{ $role->name }} <span class="badge text-bg-light border">{{ $role->users_count }}</span></button></li>
    @endforeach
</ul>
<div class="tab-content">
    @foreach ($roles as $role)
        @php($granted = $role->permissions->pluck('id')->all())
        @php($locked = $role->slug === 'super_admin')
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pane-role-{{ $role->id }}">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf @method('PUT')
                <x-card class="mb-3">
                    <div class="row align-items-end">
                        <div class="col-md-9">
                            <label class="form-label" for="desc-{{ $role->id }}">Description</label>
                            <input id="desc-{{ $role->id }}" name="description" class="form-control" value="{{ $role->description }}" @disabled($locked)>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            @if ($locked)
                                <span class="op-badge op-soft-purple"><i class="bi bi-lock"></i> Always has full access</span>
                            @else
                                <button class="btn btn-primary"><i class="bi bi-check2"></i> Save permissions</button>
                            @endif
                        </div>
                    </div>
                </x-card>
                <div class="row g-3">
                    @foreach ($permissions as $module => $perms)
                        <div class="col-md-6 col-xl-4">
                            <x-card :title="$module" class="h-100">
                                @foreach ($perms as $perm)
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="permissions[]" value="{{ $perm->id }}" id="p-{{ $role->id }}-{{ $perm->id }}" @checked($locked || in_array($perm->id, $granted)) @disabled($locked)>
                                        <label class="form-check-label small" for="p-{{ $role->id }}-{{ $perm->id }}">{{ $perm->name }} <code class="small text-muted ms-1">{{ $perm->slug }}</code></label>
                                    </div>
                                @endforeach
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    @endforeach
</div>
<p class="small text-muted mt-4"><i class="bi bi-info-circle"></i> Every signed-in user can always access their own profile, attendance, leave, tasks, payslips and visible documents regardless of role.</p>
@endsection
