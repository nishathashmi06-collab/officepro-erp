@extends('layouts.app')
@section('title', 'Request leave')
@section('content')
<x-page-header title="Request leave" subtitle="Weekends are excluded automatically. Your manager and HR will be notified." :breadcrumbs="['Leave' => route('leaves.index'), 'New request' => null]" />
<div class="row g-4">
    <div class="col-xl-7">
        <x-card>
            <form method="POST" action="{{ route('leaves.store') }}" enctype="multipart/form-data" data-leave-form novalidate>
                @csrf
                @if ($employees->isNotEmpty())
                    <x-form.select name="employee_id" label="Employee" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.')'])" :value="auth()->user()->employee?->id" :placeholder="auth()->user()->employee ? null : 'Select employee…'" help="As HR you can file leave on behalf of an employee." />
                @endif
                <div class="mb-3">
                    <label for="f_leave_type_id" class="form-label">Leave type<span class="req">*</span></label>
                    <select name="leave_type_id" id="f_leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                        <option value="">Select leave type…</option>
                        @foreach ($types as $t)
                            <option value="{{ $t->id }}" data-requires-attachment="{{ $t->requires_attachment ? 1 : 0 }}" @selected(old('leave_type_id') == $t->id)>{{ $t->name }}{{ $t->days_per_year ? ' ('.$t->days_per_year.' days/yr)' : ' (unpaid)' }}</option>
                        @endforeach
                    </select>
                    @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <x-form.input class="col-md-6" name="start_date" type="date" label="From" required min="{{ today()->subDays(30)->toDateString() }}" />
                    <x-form.input class="col-md-6" name="end_date" type="date" label="To" required />
                </div>
                <div class="op-alert op-alert-info py-2"><i class="bi bi-calendar-week lead-icon"></i><div>Duration: <strong data-leave-days>—</strong></div></div>
                <x-form.textarea name="reason" label="Reason" required rows="3" placeholder="Briefly explain the reason for your leave" />
                <x-form.input name="attachment" type="file" label="Supporting document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" help="PDF, image or Word, max 5 MB. E.g. a medical certificate." />
                <div class="small text-danger d-none mb-2" data-attachment-required><i class="bi bi-paperclip"></i> This leave type requires an attachment.</div>
                <x-form.actions :cancel="route('leaves.index')" submit="Submit request" />
            </form>
        </x-card>
    </div>
    @if ($balances->isNotEmpty())
        <div class="col-xl-5">
            <x-card title="Your balance {{ now()->year }}" icon="bi-umbrella" :padding="false">
                @foreach ($balances as $b)
                    <div class="op-list-item">
                        <span class="op-list-icon op-soft-{{ $b->type->color }}"><i class="bi bi-calendar2"></i></span>
                        <div class="flex-fill"><div class="fw-semibold">{{ $b->type->name }}</div><div class="small text-muted">{{ (float) $b->used }} used · {{ (float) $b->pending }} pending</div></div>
                        <div class="text-end"><div class="h5 mb-0">{{ $b->remaining === null ? '∞' : (float) $b->remaining }}</div><div class="small text-muted">left</div></div>
                    </div>
                @endforeach
            </x-card>
        </div>
    @endif
</div>
@endsection
