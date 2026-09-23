@extends('layouts.app')
@section('title', $expense->title)
@section('content')
<x-page-header :title="$expense->title" :subtitle="money($expense->amount).' · '.$expense->category?->name" :breadcrumbs="['Expenses' => route('expenses.index'), 'Expense #'.$expense->id => null]">
    <x-slot:actions>
        @can('update', $expense)<a href="{{ route('expenses.edit', $expense) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Edit</a>@endcan
        @can('delete', $expense)<x-delete-button :action="route('expenses.destroy', $expense)" title="Delete this expense?" label="Delete" size="" />@endcan
    </x-slot:actions>
</x-page-header>
<div class="row g-4">
    <div class="col-xl-7">
        <x-card>
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div><div class="small text-muted">Amount</div><div class="display-6 fw-bold">{{ money($expense->amount) }}</div></div>
                <x-status-badge :status="$expense->status" />
            </div>
            <dl class="op-dl">
                <dt>Category</dt><dd>{{ $expense->category?->name }}</dd>
                <dt>Date</dt><dd>{{ fmt_date($expense->date) }}</dd>
                <dt>Payment method</dt><dd>{{ label($expense->payment_method) }}</dd>
                <dt>Submitted by</dt><dd>{{ $expense->creator?->name ?? '—' }} <span class="text-muted">· {{ $expense->created_at->format('M d, Y') }}</span></dd>
                <dt>Description</dt><dd class="fw-normal">{{ $expense->description ?: '—' }}</dd>
                <dt>Receipt</dt><dd>@if($expense->receipt)<a href="{{ route('expenses.receipt', $expense) }}"><i class="bi bi-download"></i> {{ $expense->receipt_name }}</a>@else <span class="text-muted">No receipt</span>@endif</dd>
                @if ($expense->reviewed_at)
                    <dt>Reviewed</dt><dd>{{ label($expense->status) }} by {{ $expense->reviewer?->name }} on {{ $expense->reviewed_at->format('M d, Y') }}</dd>
                    @if ($expense->review_note)<dt>Review note</dt><dd class="fw-normal">{{ $expense->review_note }}</dd>@endif
                @endif
            </dl>
        </x-card>
    </div>
    @can('review', $expense)
        <div class="col-xl-5">
            <x-card title="Approve or reject" icon="bi-clipboard-check">
                <form method="POST" action="{{ route('expenses.approve', $expense) }}" novalidate>
                    @csrf
                    <x-form.textarea name="review_note" label="Note" rows="2" placeholder="Required when rejecting" />
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" formaction="{{ route('expenses.reject', $expense) }}" class="btn btn-soft-danger"><i class="bi bi-x-lg"></i> Reject</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Approve</button>
                    </div>
                </form>
            </x-card>
        </div>
    @endcan
</div>
@endsection
