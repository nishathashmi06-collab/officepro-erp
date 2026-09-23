@extends('layouts.app')
@php($editing = $department->exists)
@section('title', $editing ? 'Edit department' : 'New department')
@section('content')
<x-page-header :title="$editing ? 'Edit '.$department->name : 'New department'" :breadcrumbs="['Departments' => route('departments.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-7">
<x-card class="op-animate">
    <form method="POST" action="{{ $editing ? route('departments.update', $department) : route('departments.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row">
            <x-form.input class="col-md-8" name="name" label="Department name" :value="$department->name" required />
            <x-form.input class="col-md-4" name="code" label="Code" :value="$department->code" placeholder="e.g. IT" />
        </div>
        <x-form.textarea name="description" label="Description" :value="$department->description" />
        <div class="row">
            <x-form.select class="col-md-8" name="manager_id" label="Department manager" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.')'])" :value="$department->manager_id" placeholder="— No manager —" help="Managers with the Manager role can view this team and approve their leave." />
            <x-form.select class="col-md-4" name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$department->status" required />
        </div>
        <x-form.actions :cancel="route('departments.index')" :submit="$editing ? 'Save changes' : 'Create department'" />
    </form>
</x-card>
</div></div>
@endsection
