@extends('layouts.app')
@section('title', 'Expense categories')
@section('content')
<x-page-header title="Expense categories" :breadcrumbs="['Expenses' => route('expenses.index'), 'Categories' => null]">
    <x-slot:actions><a href="{{ route('expense-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New category</a></x-slot:actions>
</x-page-header>
<x-card :padding="false">
    <x-table>
        <thead><tr><th>Name</th><th>Description</th><th>Expenses</th><th class="text-end">Approved total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach ($categories as $c)
            <tr>
                <td data-label="Name" class="fw-semibold">{{ $c->name }}</td>
                <td data-label="Description" class="text-muted">{{ $c->description ?? '—' }}</td>
                <td data-label="Expenses"><a href="{{ route('expenses.index', ['category' => $c->id]) }}">{{ $c->expenses_count }}</a></td>
                <td data-label="Approved total" class="text-end">{{ money($c->expenses_sum_amount ?? 0) }}</td>
                <td data-label="Status"><x-status-badge :status="$c->status" /></td>
                <td class="actions" data-label="">
                    <a href="{{ route('expense-categories.edit', $c) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    <x-delete-button :action="route('expense-categories.destroy', $c)" title="Delete {{ $c->name }}?" />
                </td>
            </tr>
        @endforeach
        </tbody>
    </x-table>
</x-card>
@endsection
