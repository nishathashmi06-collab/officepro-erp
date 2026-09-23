@extends('layouts.app')
@section('title', 'Edit payroll')
@section('content')
<x-page-header title="Edit payroll" :subtitle="$payroll->employee->full_name.' · '.$payroll->period_label" :breadcrumbs="['Payroll' => route('payroll.index'), $payroll->period_label => route('payroll.show', $payroll), 'Edit' => null]" />
<form method="POST" action="{{ route('payroll.update', $payroll) }}" data-payroll-form data-currency="{{ setting('currency_symbol') }}" novalidate>
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-xl-8">
            <x-card>
                <div class="op-form-section">Earnings</div>
                <div class="row">
                    @foreach (['basic_salary' => 'Basic salary', 'allowances' => 'Allowances', 'overtime' => 'Overtime', 'bonus' => 'Bonus'] as $f => $lbl)
                        <x-form.input class="col-md-6" :name="$f" type="number" step="0.01" min="0" :label="$lbl" :value="$payroll->$f" :prefix="setting('currency_symbol')" required />
                    @endforeach
                </div>
                <div class="op-form-section">Deductions</div>
                <div class="row">
                    @foreach (['deductions' => 'Deductions (e.g. unpaid leave)', 'tax' => 'Tax', 'other_deductions' => 'Other deductions'] as $f => $lbl)
                        <x-form.input class="col-md-4" :name="$f" type="number" step="0.01" min="0" :label="$lbl" :value="$payroll->$f" :prefix="setting('currency_symbol')" required />
                    @endforeach
                </div>
                <div class="op-form-section">Status</div>
                <div class="row">
                    <x-form.select class="col-md-6" name="status" label="Status" :options="['draft' => 'Draft', 'pending' => 'Pending approval', 'approved' => 'Approved']" :value="$payroll->status" required />
                    <x-form.input class="col-md-6" name="payment_method" label="Payment method" :value="$payroll->payment_method" placeholder="e.g. bank_transfer" />
                </div>
                <x-form.textarea name="notes" label="Notes" :value="$payroll->notes" rows="2" />
                <x-form.actions :cancel="route('payroll.show', $payroll)" />
            </x-card>
        </div>
        <div class="col-xl-4">
            <div class="op-card p-4 position-sticky" style="top: 90px">
                <div class="small-caps mb-3">Live summary</div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Gross salary</span><strong data-gross></strong></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Total deductions</span><strong class="text-danger" data-total-deductions></strong></div>
                <hr>
                <div class="d-flex justify-content-between align-items-center"><span class="fw-semibold">Net salary</span><span class="h4 mb-0" data-net></span></div>
            </div>
        </div>
    </div>
</form>
@endsection
