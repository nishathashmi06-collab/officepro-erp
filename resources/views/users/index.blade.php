@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-page-header title="Users" subtitle="Login accounts, roles and access status." :breadcrumbs="['Users' => null]">
    <x-slot:actions>
        @can('roles.manage')<a href="{{ route('roles.index') }}" class="btn btn-light"><i class="bi bi-shield-lock"></i> Roles & permissions</a>@endcan
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add user</a>
    </x-slot:actions>
</x-page-header>
<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
        <div class="col-md-5"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or email"></div>
        <div class="col-md-3 col-6"><label class="form-label small">Role</label><select name="role" class="form-select"><option value="">All roles</option>@foreach ($roles as $r)<option value="{{ $r->id }}" @selected(request('role') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
        <div class="col-md-3 col-6"><label class="form-label small">Status</label><select name="status" class="form-select"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="disabled" @selected(request('status') === 'disabled')>Disabled</option></select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <x-table>
        <thead><tr><th>User</th><th>Role</th><th>Employee</th><th>Last login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr data-href="{{ route('users.show', $u) }}">
                <td data-label=""><div class="op-person"><x-avatar :name="$u->name" :src="$u->photo_url" size="36" /><span class="min-w-0"><span class="name">{{ $u->name }} @if($u->is_primary)<i class="bi bi-patch-check-fill text-primary" title="Primary super admin"></i>@endif</span><span class="sub d-block">{{ $u->email }}</span></span></div></td>
                <td data-label="Role"><span class="op-badge {{ $u->isPrivileged() ? 'op-soft-purple' : 'op-soft-info' }} no-dot">{{ $u->role?->name }}</span></td>
                <td data-label="Employee" class="small">{{ $u->employee?->employee_code ?? '—' }}</td>
                <td data-label="Last login" class="small">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                <td data-label="Status"><x-status-badge :status="$u->status" /></td>
                <td class="actions" data-label="">
                    @can('update', $u)<a href="{{ route('users.edit', $u) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                    @can('toggleStatus', $u)
                        <form method="POST" action="{{ route('users.toggle-status', $u) }}" class="d-inline">@csrf<button class="btn btn-light btn-sm btn-icon" title="{{ $u->status === 'active' ? 'Disable' : 'Enable' }}"><i class="bi bi-{{ $u->status === 'active' ? 'person-slash' : 'person-check' }}"></i></button></form>
                    @endcan
                    @can('delete', $u)<x-delete-button :action="route('users.destroy', $u)" title="Delete {{ $u->name }}?" message="The account will be removed and can no longer sign in." />@endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </x-table>
    {{ $users->links() }}
</x-card>
@endsection
