@extends('layouts.app')
@php($editing = $expense->exists)
@section('title', $editing ? 'Edit expense' : 'Add expense')
@section('content')
<x-page-header :title="$editing ? 'Edit expense' : 'Add expense'" subtitle="New expenses are submitted as pending for approval." :breadcrumbs="['Expenses' => route('expenses.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-8">
<x-card>
    <form method="POST" action="{{ $editing ? route('expenses.update', $expense) : route('expenses.store') }}" enctype="multipart/form-data" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <x-form.input name="title" label="Title" :value="$expense->title" required placeholder="e.g. Printer toner" />
        <div class="row">
            <x-form.select class="col-md-6" name="expense_category_id" label="Category" :options="$categories->pluck('name', 'id')" :value="$expense->expense_category_id" placeholder="Select category…" required />
            <x-form.input class="col-md-6" name="amount" type="number" step="0.01" min="0.01" label="Amount" :value="$expense->amount" :prefix="setting('currency_symbol')" required />
            <x-form.input class="col-md-6" name="date" type="date" label="Date" :value="$expense->date?->toDateString()" max="{{ today()->toDateString() }}" required />
            <x-form.select class="col-md-6" name="payment_method" label="Payment method" :options="collect(App\Models\Expense::PAYMENT_METHODS)->mapWithKeys(fn ($m) => [$m => label($m)])" :value="$expense->payment_method" required />
        </div>
        <x-form.textarea name="description" label="Description" :value="$expense->description" rows="3" />
        <x-form.input name="receipt" type="file" label="Receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" help="PDF or image, max 5 MB. Stored privately." />
        @if ($expense->receipt)
            <div class="d-flex align-items-center gap-3 mb-3">
                <a href="{{ route('expenses.receipt', $expense) }}" class="small"><i class="bi bi-paperclip"></i> {{ $expense->receipt_name }}</a>
                <x-form.checkbox name="remove_receipt" label="Remove receipt" :switch="false" class="mb-0" />
            </div>
        @endif
        <x-form.actions :cancel="$editing ? route('expenses.show', $expense) : route('expenses.index')" :submit="$editing ? 'Save changes' : 'Submit expense'" />
    </form>
</x-card>
</div></div>
@endsection
