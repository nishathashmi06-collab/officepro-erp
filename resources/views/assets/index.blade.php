@extends('layouts.app')
@section('title', 'Assets')
@section('content')
<x-page-header title="Assets" subtitle="Company equipment register with assignment and maintenance history." :breadcrumbs="['Assets' => null]">
    <x-slot:actions>
        @can('assets.manage')<a href="{{ route('assets.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add asset</a>@endcan
    </x-slot:actions>
</x-page-header>
<div class="row g-3 mb-4">
    @foreach (['available' => ['bi-box-seam', 'success'], 'assigned' => ['bi-person-check', 'primary'], 'maintenance' => ['bi-tools', 'warning'], 'retired' => ['bi-archive', 'secondary']] as $s => [$icon, $color])
        <div class="col-6 col-lg op-animate op-animate-{{ $loop->iteration }}"><x-stat-card :label="label($s)" :value="$counts[$s] ?? 0" :icon="$icon" :color="$color" :href="route('assets.index', ['status' => $s])" /></div>
    @endforeach
    <div class="col-12 col-lg op-animate op-animate-5"><x-stat-card label="Asset value" :value="money($totalValue)" icon="bi-currency-exchange" color="info" meta="Excluding retired" /></div>
</div>
<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
        <div class="col-md-4"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, asset ID or serial"></div>
        <div class="col-md-2 col-6"><label class="form-label small">Category</label>
            <select name="category" class="form-select"><option value="">All</option>@foreach (App\Models\Asset::CATEGORIES as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ label($c) }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6"><label class="form-label small">Status</label>
            <select name="status" class="form-select"><option value="">All</option>@foreach (App\Models\Asset::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small">Assigned to</label>
            <select name="employee" class="form-select"><option value="">Anyone</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('employee') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    @if ($assets->isEmpty())
        <x-empty-state icon="bi-laptop" title="No assets found" />
    @else
        <x-table>
            <thead><tr><th>Asset</th><th>Category</th><th>Serial no.</th><th>Assigned to</th><th>Condition</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($assets as $a)
                <tr data-href="{{ route('assets.show', $a) }}">
                    <td data-label="Asset"><div class="fw-semibold">{{ $a->name }}</div><div class="small text-muted font-monospace">{{ $a->asset_code }}</div></td>
                    <td data-label="Category">{{ label($a->category) }}</td>
                    <td data-label="Serial no." class="small font-monospace">{{ $a->serial_number ?? '—' }}</td>
                    <td data-label="Assigned to">@if($a->employee)<x-person :employee="$a->employee" size="28" />@else<span class="text-muted">—</span>@endif</td>
                    <td data-label="Condition"><x-status-badge :status="$a->condition" /></td>
                    <td data-label="Status"><x-status-badge :status="$a->status" /></td>
                    <td class="actions" data-label="">
                        <a href="{{ route('assets.show', $a) }}" class="btn btn-light btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        @can('assets.manage')<a href="{{ route('assets.edit', $a) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $assets->links() }}
    @endif
</x-card>
@endsection
