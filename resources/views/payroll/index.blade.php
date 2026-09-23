@extends('layouts.app')
@section('title', $viewAll ? 'Payroll' : 'My Payslips')
@section('content')
<x-page-header :title="$viewAll ? 'Payroll' : 'My Payslips'" :subtitle="$viewAll ? 'Generate, review, approve and pay monthly salaries.' : 'Your approved salary records and downloadable slips.'" :breadcrumbs="['Payroll' => null]">
    <x-slot:actions>
        @if ($manage)
            @if ($month)
                <form method="POST" action="{{ route('payroll.bulk-approve') }}" data-confirm="All draft and pending records for this month will be approved and employees notified." data-confirm-title="Approve payroll for {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}?" data-confirm-button="Approve all" data-confirm-variant="primary">
                    @csrf<input type="hidden" name="month" value="{{ $month }}"><button class="btn btn-light"><i class="bi bi-check2-all"></i> Approve month</button>
                </form>
            @endif
            <a href="{{ route('payroll.create') }}" class="btn btn-primary"><i class="bi bi-lightning-charge"></i> Generate payroll</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-md-4 op-animate op-animate-1"><x-stat-card label="Records" :value="$totals->records" icon="bi-files" color="primary" :meta="$month ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') : 'All months'" /></div>
    <div class="col-md-4 op-animate op-animate-2"><x-stat-card label="Total gross" :value="money($totals->gross)" icon="bi-graph-up-arrow" color="info" meta="Basic + allowances + overtime + bonus" /></div>
    <div class="col-md-4 op-animate op-animate-3"><x-stat-card label="Total net pay" :value="money($totals->net)" icon="bi-wallet2" color="success" meta="After deductions and tax" /></div>
</div>

<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
        <div class="col-md-3 col-6"><label class="form-label small">Salary month</label><input type="month" name="month" value="{{ $month }}" class="form-control"></div>
        <div class="col-md-2 col-6"><label class="form-label small">Status</label>
            <select name="status" class="form-select"><option value="">All</option>@foreach (App\Models\Payroll::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach</select></div>
        @if ($viewAll)
            <div class="col-md-2 col-6"><label class="form-label small">Department</label>
                <select name="department" class="form-select"><option value="">All</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-3 col-6"><label class="form-label small">Employee</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or ID"></div>
        @endif
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>@if(request()->query())<a href="{{ route('payroll.index') }}" class="btn btn-light btn-icon"><i class="bi bi-x-lg"></i></a>@endif</div>
    </form>

    @if ($payrolls->isEmpty())
        <x-empty-state icon="bi-cash-stack" title="No payroll records" :message="$viewAll ? 'Generate payroll for a month to get started.' : 'Your salary slips will appear here once payroll is approved.'">
            @if ($manage)<a href="{{ route('payroll.create') }}" class="btn btn-primary">Generate payroll</a>@endif
        </x-empty-state>
    @else
        <x-table>
            <thead><tr><th>Month</th>@if($viewAll)<th>Employee</th>@endif<th class="text-end">Basic</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($payrolls as $p)
                <tr data-href="{{ route('payroll.show', $p) }}">
                    <td data-label="Month" class="fw-semibold">{{ $p->period_label }}</td>
                    @if($viewAll)<td data-label=""><x-person :employee="$p->employee" size="30" :sub="$p->employee?->department?->name" /></td>@endif
                    <td data-label="Basic" class="text-end">{{ money($p->basic_salary) }}</td>
                    <td data-label="Gross" class="text-end">{{ money($p->gross_salary) }}</td>
                    <td data-label="Deductions" class="text-end text-danger">−{{ money($p->total_deductions) }}</td>
                    <td data-label="Net" class="text-end fw-bold">{{ money($p->net_salary) }}</td>
                    <td data-label="Status"><x-status-badge :status="$p->status" /></td>
                    <td class="actions" data-label="">
                        @can('approve', $p)
                            <form method="POST" action="{{ route('payroll.approve', $p) }}" class="d-inline">@csrf<button class="btn btn-soft-success btn-sm btn-icon" title="Approve" data-bs-toggle="tooltip"><i class="bi bi-check-lg"></i></button></form>
                        @endcan
                        @can('update', $p)<a href="{{ route('payroll.edit', $p) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                        <a href="{{ route('payroll.slip', [$p, 'download' => 1]) }}" class="btn btn-soft-primary btn-sm btn-icon" title="Salary slip PDF" data-bs-toggle="tooltip"><i class="bi bi-file-earmark-pdf"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $payrolls->links() }}
    @endif
</x-card>
@endsection
