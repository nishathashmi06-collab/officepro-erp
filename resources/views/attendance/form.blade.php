@extends('layouts.app')
@php($editing = $attendance->exists)
@section('title', $editing ? 'Edit attendance' : 'Add attendance')
@section('content')
<x-page-header :title="$editing ? 'Edit attendance' : 'Add attendance record'" subtitle="Working hours are calculated automatically from check-in and check-out." :breadcrumbs="['Attendance' => route('attendance.index'), ($editing ? 'Edit' : 'New') => null]" />
<div class="row"><div class="col-xl-7">
<x-card>
    <form method="POST" action="{{ $editing ? route('attendance.update', $attendance) : route('attendance.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row">
            <x-form.select class="col-md-7" name="employee_id" label="Employee" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.')'])" :value="$attendance->employee_id" placeholder="Select employee…" required />
            <x-form.input class="col-md-5" name="date" type="date" label="Date" :value="$attendance->date?->toDateString()" max="{{ today()->toDateString() }}" required />
            <x-form.select class="col-md-4" name="status" label="Status" :options="collect(App\Models\Attendance::STATUSES)->mapWithKeys(fn ($s) => [$s => label($s)])" :value="$attendance->status" required data-attendance-status />
            <div class="col-md-4" data-attendance-time><x-form.input name="check_in" type="time" label="Check in" :value="$attendance->check_in ? substr($attendance->check_in, 0, 5) : ''" /></div>
            <div class="col-md-4" data-attendance-time><x-form.input name="check_out" type="time" label="Check out" :value="$attendance->check_out ? substr($attendance->check_out, 0, 5) : ''" /></div>
        </div>
        <x-form.textarea name="notes" label="Notes" :value="$attendance->notes" rows="2" placeholder="Reason for manual change, e.g. forgot to check out" />
        <x-form.actions :cancel="route('attendance.index')" />
    </form>
</x-card>
</div></div>
@endsection
