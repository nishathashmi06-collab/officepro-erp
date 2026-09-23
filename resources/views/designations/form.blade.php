@extends('layouts.app')
@php($editing = $designation->exists)
@section('title', $editing ? 'Edit designation' : 'New designation')
@section('content')
<x-page-header :title="$editing ? 'Edit designation' : 'New designation'" :breadcrumbs="['Designations' => route('designations.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-6">
<x-card>
    <form method="POST" action="{{ $editing ? route('designations.update', $designation) : route('designations.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <x-form.input name="name" label="Title" :value="$designation->name" required />
        <x-form.textarea name="description" label="Description" :value="$designation->description" />
        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$designation->status" required />
        <x-form.actions :cancel="route('designations.index')" />
    </form>
</x-card>
</div></div>
@endsection
