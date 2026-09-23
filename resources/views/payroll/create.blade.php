@extends('layouts.app')
@section('title', 'Generate payroll')
@section('content')
<x-page-header title="Generate payroll" subtitle="Creates draft salary records from each employee's monthly basic salary." :breadcrumbs="['Payroll' => route('payroll.index'), 'Generate' => null]" />
<div class="row g-4">
    <div class="col-xl-7">
        <x-card>
            <form method="POST" action="{{ route('payroll.store') }}" novalidate>
                @csrf
                <div class="row">
                    <x-form.input class="col-md-6" name="month" type="month" label="Salary month" :value="$defaultMonth" required />
                    <x-form.select class="col-md-6" name="department_id" label="Department" :options="$departments->pluck('name', 'id')" placeholder="All departments" />
                    <x-form.input class="col-md-6" name="allowance_percent" type="number" step="0.1" min="0" max="100" label="Allowances" :value="10" suffix="% of basic" required />
                    <x-form.input class="col-md-6" name="tax_percent" type="number" step="0.1" min="0" max="100" label="Tax" :value="8" suffix="% of basic + allowances" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Employees <span class="text-muted fw-normal">(optional — leave empty for all active employees)</span></label>
                    <select name="employee_ids[]" class="form-select" multiple size="8">
                        @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(in_array($e->id, old('employee_ids', [])))>{{ $e->full_name }} · {{ $e->department?->name ?? '—' }} · {{ money($e->salary) }}</option>@endforeach
                    </select>
                    <div class="form-text">Hold Ctrl / Cmd to select multiple.</div>
                </div>
                <x-form.actions :cancel="route('payroll.index')" submit="Generate draft payroll" />
            </form>
        </x-card>
    </div>
    <div class="col-xl-5">
        <x-card title="How it's calculated" icon="bi-calculator">
            <div class="p-3 rounded-3 mb-3 font-monospace small" style="background:var(--op-surface-2)">
                Gross = Basic + Allowances + Overtime + Bonus<br>
                Net&nbsp;&nbsp; = Gross − Deductions − Tax − Other deductions
            </div>
            <ul class="small text-muted ps-3 mb-0">
                <li>Basic salary is taken from the employee record.</li>
                <li>Approved <strong>unpaid leave</strong> in the month is deducted pro rata per working day.</li>
                <li>Employees who already have a record for the month are skipped.</li>
                <li>Records start as <strong>Draft</strong> — edit overtime/bonus, then approve and mark as paid.</li>
            </ul>
        </x-card>
    </div>
</div>
@endsection
