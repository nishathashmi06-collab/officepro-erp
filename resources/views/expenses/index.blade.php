@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<x-page-header title="Expenses" subtitle="Record office spending, attach receipts and manage approvals." :breadcrumbs="['Expenses' => null]">
    <x-slot:actions>
        @can('expenses.manage')<a href="{{ route('expense-categories.index') }}" class="btn btn-light"><i class="bi bi-tags"></i> Categories</a>@endcan
        @if (auth()->user()->can('reports.view') && auth()->user()->can('expenses.view_all'))
            <a href="{{ route('reports.show', ['expenses', 'from' => request('from'), 'to' => request('to')]) }}" class="btn btn-light"><i class="bi bi-bar-chart-line"></i> Report</a>
        @endif
        @can('create', App\Models\Expense::class)<a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add expense</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach (['approved' => ['Approved', 'bi-check2-circle', 'success'], 'pending' => ['Pending approval', 'bi-hourglass-split', 'warning'], 'rejected' => ['Rejected', 'bi-x-circle', 'danger']] as $s => [$lbl, $icon, $color])
        <div class="col-md-4 op-animate op-animate-{{ $loop->iteration }}"><x-stat-card :label="$lbl" :value="money($summary[$s]->total ?? 0)" :icon="$icon" :color="$color" :meta="($summary[$s]->records ?? 0).' expense(s) in current filter'" /></div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-9">
        <x-card :padding="false">
            <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
                <div class="col-md-3"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Title…"></div>
                <div class="col-md-2 col-6"><label class="form-label small">Category</label>
                    <select name="category" class="form-select"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-2 col-6"><label class="form-label small">Status</label>
                    <select name="status" class="form-select"><option value="">All</option>@foreach (App\Models\Expense::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach</select></div>
                <div class="col-md-2 col-6"><label class="form-label small">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2 col-6"><label class="form-label small">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
            </form>
            @if ($expenses->isEmpty())
                <x-empty-state icon="bi-receipt" title="No expenses found" message="Adjust filters or record a new expense." />
            @else
                <x-table>
                    <thead><tr><th>Expense</th><th>Category</th><th>Date</th><th class="text-end">Amount</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach ($expenses as $x)
                        <tr data-href="{{ route('expenses.show', $x) }}">
                            <td data-label="Expense"><div class="fw-semibold">{{ $x->title }} @if($x->receipt)<i class="bi bi-paperclip text-muted" title="Receipt attached"></i>@endif</div><div class="small text-muted">{{ $x->creator?->name ?? '—' }} · {{ label($x->payment_method) }}</div></td>
                            <td data-label="Category"><span class="op-badge op-soft-secondary no-dot">{{ $x->category?->name }}</span></td>
                            <td data-label="Date">{{ fmt_date($x->date) }}</td>
                            <td data-label="Amount" class="text-end fw-bold">{{ money($x->amount) }}</td>
                            <td data-label="Status"><x-status-badge :status="$x->status" /></td>
                            <td class="actions" data-label="">
                                @can('review', $x)
                                    <form method="POST" action="{{ route('expenses.approve', $x) }}" class="d-inline">@csrf<button class="btn btn-soft-success btn-sm btn-icon" title="Approve"><i class="bi bi-check-lg"></i></button></form>
                                @endcan
                                @can('update', $x)<a href="{{ route('expenses.edit', $x) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                                @can('delete', $x)<x-delete-button :action="route('expenses.destroy', $x)" title="Delete this expense?" />@endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </x-table>
                {{ $expenses->links() }}
            @endif
        </x-card>
    </div>
    <div class="col-xl-3">
        <x-card title="This month by category" icon="bi-pie-chart" subtitle="Approved spend">
            @php($max = max(1, (float) $byCategory->max('total')))
            @forelse ($byCategory as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $row->category }}</span><strong>{{ money($row->total) }}</strong></div>
                    <div class="op-progress"><div class="bar" style="width: {{ round($row->total / $max * 100) }}%"></div></div>
                </div>
            @empty
                <p class="small text-muted mb-0">No approved expenses this month.</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
