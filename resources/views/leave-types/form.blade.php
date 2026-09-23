@extends('layouts.app')
@php($editing = $type->exists)
@section('title', $editing ? 'Edit leave type' : 'New leave type')
@section('content')
<x-page-header :title="$editing ? 'Edit '.$type->name : 'New leave type'" :breadcrumbs="['Leave' => route('leaves.index'), 'Types' => route('leave-types.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-6">
<x-card>
    <form method="POST" action="{{ $editing ? route('leave-types.update', $type) : route('leave-types.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row">
            <x-form.input class="col-md-8" name="name" label="Name" :value="$type->name" required />
            <x-form.input class="col-md-4" name="code" label="Code" :value="$type->code" required />
            <x-form.input class="col-md-6" name="days_per_year" type="number" min="0" max="365" label="Days per year" :value="$type->days_per_year ?? 0" required help="0 = unlimited (e.g. unpaid leave)" />
            <x-form.select class="col-md-6" name="color" label="Colour" :options="['primary' => 'Indigo', 'success' => 'Green', 'info' => 'Blue', 'warning' => 'Amber', 'danger' => 'Red', 'secondary' => 'Grey']" :value="$type->color" required />
        </div>
        <x-form.checkbox name="is_paid" label="Paid leave" :checked="$type->is_paid" help="Unpaid leave days are deducted when payroll is generated." />
        <x-form.checkbox name="requires_attachment" label="Requires supporting document" :checked="$type->requires_attachment" />
        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$type->status" required />
        <x-form.actions :cancel="route('leave-types.index')" />
    </form>
</x-card>
</div></div>
@endsection
