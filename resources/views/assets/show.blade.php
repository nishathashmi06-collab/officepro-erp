@extends('layouts.app')
@section('title', $asset->name)
@section('content')
<x-page-header :title="$asset->name" :subtitle="$asset->asset_code.' · '.label($asset->category)" :breadcrumbs="['Assets' => route('assets.index'), $asset->asset_code => null]">
    <x-slot:actions>
        @can('assets.manage')
            @if ($asset->status === 'available')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assign-modal"><i class="bi bi-person-plus"></i> Assign</button>
            @elseif ($asset->status === 'assigned')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#return-modal"><i class="bi bi-arrow-return-left"></i> Return</button>
            @endif
            @if (in_array($asset->status, ['available', 'maintenance']))
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#maint-modal"><i class="bi bi-tools"></i> Log maintenance</button>
            @endif
            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Edit</a>
            <x-delete-button :action="route('assets.destroy', $asset)" title="Delete this asset?" message="Assigned assets must be returned first." />
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-xl-4">
        <x-card class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="op-list-icon op-soft-primary" style="width:52px;height:52px;font-size:1.4rem"><i class="bi bi-laptop"></i></span>
                <x-status-badge :status="$asset->status" />
            </div>
            <dl class="op-dl">
                <dt>Asset ID</dt><dd class="font-monospace">{{ $asset->asset_code }}</dd>
                <dt>Serial no.</dt><dd class="font-monospace">{{ $asset->serial_number ?? '—' }}</dd>
                <dt>Category</dt><dd>{{ label($asset->category) }}</dd>
                <dt>Condition</dt><dd><x-status-badge :status="$asset->condition" /></dd>
                <dt>Location</dt><dd>{{ $asset->location ?? '—' }}</dd>
                <dt>Purchased</dt><dd>{{ fmt_date($asset->purchase_date) }}</dd>
                <dt>Price</dt><dd>{{ $asset->purchase_price !== null ? money($asset->purchase_price) : '—' }}</dd>
                <dt>Assigned to</dt><dd>@if($asset->employee)<a href="{{ route('employees.show', $asset->employee) }}">{{ $asset->employee->full_name }}</a>@else — @endif</dd>
            </dl>
            @if ($asset->notes)<p class="small text-muted mt-3 mb-0">{{ $asset->notes }}</p>@endif
        </x-card>
    </div>
    <div class="col-xl-8">
        <x-card title="Assignment history" icon="bi-people" :padding="false" class="mb-4">
            @if ($asset->assignments->isEmpty())
                <x-empty-state icon="bi-person-x" title="Never assigned" />
            @else
                <x-table>
                    <thead><tr><th>Employee</th><th>Assigned</th><th>Returned</th><th>Condition</th><th>By</th></tr></thead>
                    <tbody>
                    @foreach ($asset->assignments as $as)
                        <tr>
                            <td data-label=""><x-person :employee="$as->employee" size="30" /></td>
                            <td data-label="Assigned">{{ fmt_date($as->assigned_at) }}</td>
                            <td data-label="Returned">{!! $as->returned_at ? e(fmt_date($as->returned_at)) : '<span class="op-badge op-soft-primary">Current</span>' !!}</td>
                            <td data-label="Condition" class="small">{{ label($as->condition_on_assign) }} → {{ $as->condition_on_return ? label($as->condition_on_return) : '…' }}</td>
                            <td data-label="By" class="small">{{ $as->assigner?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        <x-card title="Maintenance history" icon="bi-tools" :padding="false" class="mb-4">
            @if ($asset->maintenances->isEmpty())
                <x-empty-state icon="bi-tools" title="No maintenance records" />
            @else
                <x-table>
                    <thead><tr><th>Work</th><th>Vendor</th><th>Started</th><th>Completed</th><th class="text-end">Cost</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($asset->maintenances as $m)
                        <tr>
                            <td data-label="Work"><div class="fw-semibold">{{ $m->title }}</div><div class="small text-muted">{{ $m->description }}</div></td>
                            <td data-label="Vendor">{{ $m->vendor ?? '—' }}</td>
                            <td data-label="Started">{{ fmt_date($m->started_at) }}</td>
                            <td data-label="Completed">{!! $m->completed_at ? e(fmt_date($m->completed_at)) : '<span class="op-badge op-soft-warning">In progress</span>' !!}</td>
                            <td data-label="Cost" class="text-end">{{ money($m->cost) }}</td>
                            <td class="actions" data-label="">
                                @if (! $m->completed_at && auth()->user()->can('assets.manage'))
                                    <button class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#complete-{{ $m->id }}">Complete</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        <x-card title="Activity" icon="bi-clock-history">
            @forelse ($history as $log)
                <div class="small mb-2"><span class="text-muted">{{ $log->created_at->format('M d, Y h:i A') }}</span> · {{ $log->description }} <span class="text-muted">({{ $log->user?->name ?? 'System' }})</span></div>
            @empty
                <p class="small text-muted mb-0">No activity recorded.</p>
            @endforelse
        </x-card>
    </div>
</div>

@can('assets.manage')
    <x-modal id="assign-modal" title="Assign asset" icon="bi-person-plus">
        <form method="POST" action="{{ route('assets.assign', $asset) }}">
            @csrf
            <div class="modal-body">
                <x-form.select name="employee_id" label="Employee" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.')'])" placeholder="Select employee…" required />
                <x-form.input name="assigned_at" type="date" label="Assignment date" :value="today()->toDateString()" max="{{ today()->toDateString() }}" required />
                <x-form.textarea name="notes" label="Notes" rows="2" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Assign</button></div>
        </form>
    </x-modal>
    <x-modal id="return-modal" title="Return asset" icon="bi-arrow-return-left">
        <form method="POST" action="{{ route('assets.return', $asset) }}">
            @csrf
            <div class="modal-body">
                <x-form.input name="returned_at" type="date" label="Return date" :value="today()->toDateString()" max="{{ today()->toDateString() }}" required />
                <x-form.select name="condition" label="Condition on return" :options="collect(App\Models\Asset::CONDITIONS)->mapWithKeys(fn ($c) => [$c => label($c)])" :value="$asset->condition" required />
                <x-form.textarea name="notes" label="Notes" rows="2" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Confirm return</button></div>
        </form>
    </x-modal>
    <x-modal id="maint-modal" title="Log maintenance" icon="bi-tools">
        <form method="POST" action="{{ route('assets.maintenance.store', $asset) }}">
            @csrf
            <div class="modal-body">
                <x-form.input name="title" label="Work description" required placeholder="e.g. Battery replacement" />
                <x-form.textarea name="description" label="Details" rows="2" />
                <div class="row">
                    <x-form.input class="col-6" name="vendor" label="Vendor" />
                    <x-form.input class="col-6" name="cost" type="number" step="0.01" min="0" label="Estimated cost" :prefix="setting('currency_symbol')" />
                </div>
                <x-form.input name="started_at" type="date" label="Start date" :value="today()->toDateString()" max="{{ today()->toDateString() }}" required />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </x-modal>
    @foreach ($asset->maintenances->whereNull('completed_at') as $m)
        <x-modal :id="'complete-'.$m->id" title="Complete maintenance" icon="bi-check2-circle">
            <form method="POST" action="{{ route('assets.maintenance.complete', [$asset, $m]) }}">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">{{ $m->title }}</p>
                    <x-form.input :id="'cd-'.$m->id" name="completed_at" type="date" label="Completion date" :value="today()->toDateString()" required />
                    <x-form.input :id="'cc-'.$m->id" name="cost" type="number" step="0.01" min="0" label="Final cost" :value="$m->cost" :prefix="setting('currency_symbol')" />
                    <x-form.select :id="'cn-'.$m->id" name="condition" label="Condition after maintenance" :options="collect(App\Models\Asset::CONDITIONS)->mapWithKeys(fn ($c) => [$c => label($c)])" :value="$asset->condition" required />
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Mark completed</button></div>
            </form>
        </x-modal>
    @endforeach
@endcan
@endsection
