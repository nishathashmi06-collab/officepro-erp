@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')
<x-page-header title="Activity Log" subtitle="An audit trail of important actions across OfficePro." :breadcrumbs="['Activity log' => null]" />
<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
        <div class="col-md-3"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Description…"></div>
        <div class="col-md-2 col-6"><label class="form-label small">User</label><select name="user" class="form-select"><option value="">All</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6"><label class="form-label small">Module</label><select name="module" class="form-select"><option value="">All</option>@foreach ($modules as $m)<option value="{{ $m }}" @selected(request('module') === $m)>{{ label($m) }}</option>@endforeach</select></div>
        <div class="col-md-1 col-6"><label class="form-label small">Action</label><select name="action" class="form-select"><option value="">All</option>@foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ label($a) }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6"><label class="form-label small">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-1 col-6"><label class="form-label small">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-1 col-6"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    @if ($logs->isEmpty())
        <x-empty-state icon="bi-activity" title="No activity found" />
    @else
        <x-table>
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Record</th><th>IP address</th></tr></thead>
            <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td data-label="When" class="small text-nowrap">{{ $log->created_at->format('M d, Y') }}<div class="text-muted">{{ $log->created_at->format('h:i:s A') }}</div></td>
                    <td data-label="User">@if($log->user)<div class="d-flex align-items-center gap-2"><x-avatar :name="$log->user->name" :src="$log->user->photo_url" size="28" /><span class="small fw-semibold">{{ $log->user->name }}</span></div>@else<span class="text-muted small">System</span>@endif</td>
                    <td data-label="Action"><span class="op-badge op-soft-primary no-dot">{{ label($log->action) }}</span></td>
                    <td data-label="Module"><i class="bi {{ $log->icon() }} text-muted"></i> {{ label($log->module) }}</td>
                    <td data-label="Description" class="small">{{ $log->description }}</td>
                    <td data-label="Record" class="small text-muted">{{ $log->record_id ? '#'.$log->record_id : '—' }}</td>
                    <td data-label="IP address" class="small font-monospace text-muted">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $logs->links() }}
    @endif
</x-card>
@endsection
