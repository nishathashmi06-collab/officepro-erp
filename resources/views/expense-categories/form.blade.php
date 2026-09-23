@extends('layouts.app')
@php($editing = $category->exists)
@section('title', $editing ? 'Edit category' : 'New category')
@section('content')
<x-page-header :title="$editing ? 'Edit category' : 'New category'" :breadcrumbs="['Expenses' => route('expenses.index'), 'Categories' => route('expense-categories.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-6">
<x-card>
    <form method="POST" action="{{ $editing ? route('expense-categories.update', $category) : route('expense-categories.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <x-form.input name="name" label="Name" :value="$category->name" required />
        <x-form.input name="description" label="Description" :value="$category->description" />
        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$category->status" required />
        <x-form.actions :cancel="route('expense-categories.index')" />
    </form>
</x-card>
</div></div>
@endsection
