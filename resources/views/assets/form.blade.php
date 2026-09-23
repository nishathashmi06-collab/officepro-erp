@extends('layouts.app')
@php($editing = $asset->exists)
@section('title', $editing ? 'Edit asset' : 'Add asset')
@section('content')
<x-page-header :title="$editing ? 'Edit '.$asset->name : 'Add asset'" :breadcrumbs="['Assets' => route('assets.index')] + ($editing ? [$asset->asset_code => route('assets.show', $asset), 'Edit' => null] : ['New' => null])" />
<div class="row"><div class="col-xl-8">
<x-card>
    <form method="POST" action="{{ $editing ? route('assets.update', $asset) : route('assets.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row">
            <x-form.input class="col-md-8" name="name" label="Asset name" :value="$asset->name" required placeholder="e.g. Dell Latitude 7440" />
            <x-form.input class="col-md-4" name="asset_code" label="Asset ID" :value="$asset->asset_code" help="Blank = auto-generate" />
            <x-form.select class="col-md-6" name="category" label="Category" :options="collect(App\Models\Asset::CATEGORIES)->mapWithKeys(fn ($c) => [$c => label($c)])" :value="$asset->category" placeholder="Select…" required />
            <x-form.input class="col-md-6" name="serial_number" label="Serial number" :value="$asset->serial_number" />
            <x-form.input class="col-md-6" name="purchase_date" type="date" label="Purchase date" :value="$asset->purchase_date?->toDateString()" />
            <x-form.input class="col-md-6" name="purchase_price" type="number" step="0.01" min="0" label="Purchase price" :value="$asset->purchase_price" :prefix="setting('currency_symbol')" />
            <x-form.select class="col-md-4" name="condition" label="Condition" :options="collect(App\Models\Asset::CONDITIONS)->mapWithKeys(fn ($c) => [$c => label($c)])" :value="$asset->condition" required />
            @if ($asset->status === 'assigned')
                <div class="col-md-4 mb-3"><label class="form-label">Status</label><div class="form-control" style="background:var(--op-surface-2)">Assigned <span class="text-muted small">(use Return)</span></div><input type="hidden" name="status" value="available"></div>
            @else
                <x-form.select class="col-md-4" name="status" label="Status" :options="['available' => 'Available', 'maintenance' => 'Maintenance', 'retired' => 'Retired']" :value="$asset->status" required />
            @endif
            <x-form.input class="col-md-4" name="location" label="Location" :value="$asset->location" placeholder="e.g. Head Office – Floor 2" />
        </div>
        <x-form.textarea name="notes" label="Notes" :value="$asset->notes" rows="2" />
        <x-form.actions :cancel="$editing ? route('assets.show', $asset) : route('assets.index')" />
    </form>
</x-card>
</div></div>
@endsection
