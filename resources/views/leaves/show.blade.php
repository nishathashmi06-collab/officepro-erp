@extends('layouts.app')
@section('title', 'Leave request')
@section('content')
<x-page-header :title="$leave->leaveType->name.' request'" :subtitle="$leave->employee->full_name.' · submitted '.$leave->created_at->format('M d, Y')" :breadcrumbs="['Leave' => route('leaves.index'), 'Request #'.$leave->id => null]">
    <x-slot:actions>
        @can('cancel', $leave)
            <form method="POST" action="{{ route('leaves.cancel', $leave) }}" data-confirm="Your leave request will be cancelled." data-confirm-title="Cancel this request?" data-confirm-button="Cancel request">
                @csrf<button class="btn btn-light"><i class="bi bi-x-circle"></i> Cancel request</button>
            </form>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-xl-8">
        <x-card class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <x-person :employee="$leave->employee" :href="route('employees.show', $leave->employee)" size="48" :sub="($leave->employee->designation?->name ?? '').' · '.($leave->employee->department?->name ?? '')" />
                <x-status-badge :status="$leave->status" class="fs-6" />
            </div>
            <div class="row g-3 text-center mb-4">
                <div class="col-4"><div class="p-3 rounded-3" style="background:var(--op-surface-2)"><div class="small text-muted">From</div><div class="fw-bold">{{ $leave->start_date->format('D, M d') }}</div></div></div>
                <div class="col-4"><div class="p-3 rounded-3" style="background:var(--op-surface-2)"><div class="small text-muted">To</div><div class="fw-bold">{{ $leave->end_date->format('D, M d') }}</div></div></div>
                <div class="col-4"><div class="p-3 rounded-3 op-soft-primary"><div class="small">Working days</div><div class="fw-bold">{{ rtrim(rtrim((string) $leave->days, '0'), '.') }}</div></div></div>
            </div>
            <h3 class="h6">Reason</h3>
            <p class="mb-3">{{ $leave->reason }}</p>
            @if ($leave->attachment)
                <a href="{{ route('leaves.attachment', $leave) }}" class="btn btn-light btn-sm"><i class="bi bi-paperclip"></i> Download attachment</a>
            @endif
            @if ($leave->reviewed_at)
                <div class="op-alert op-alert-{{ $leave->status === 'approved' ? 'success' : 'danger' }} mt-4 mb-0">
                    <i class="bi bi-person-check lead-icon"></i>
                    <div><strong>{{ label($leave->status) }}</strong> by {{ $leave->reviewer?->name ?? 'a reviewer' }} on {{ $leave->reviewed_at->format('M d, Y h:i A') }}@if($leave->review_note)<div class="mt-1">“{{ $leave->review_note }}”</div>@endif</div>
                </div>
            @endif
        </x-card>

        @can('review', $leave)
            <x-card title="Review this request" icon="bi-clipboard-check">
                <form method="POST" action="{{ route('leaves.approve', $leave) }}" id="review-form" novalidate>
                    @csrf
                    <x-form.textarea name="review_note" label="Note to employee" rows="2" placeholder="Optional for approval, required when rejecting" />
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" formaction="{{ route('leaves.reject', $leave) }}" class="btn btn-soft-danger"><i class="bi bi-x-lg"></i> Reject</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Approve</button>
                    </div>
                </form>
            </x-card>
        @endcan
    </div>
    <div class="col-xl-4">
        <x-card title="Balance {{ $leave->start_date->year }}" icon="bi-umbrella" :padding="false" class="mb-4">
            @foreach ($balances as $b)
                <div class="op-list-item {{ $b->type->id == $leave->leave_type_id ? 'fw-semibold' : '' }}">
                    <div class="flex-fill">{{ $b->type->name }}</div>
                    <div class="small">{{ $b->remaining === null ? (float) $b->used.' used' : (float) $b->remaining.' / '.$b->allowed }}</div>
                </div>
            @endforeach
        </x-card>
        <x-card title="Team members away" subtitle="Overlapping approved leave in the same department" :padding="false">
            @forelse ($overlapping as $o)
                <div class="op-list-item"><x-person :employee="$o->employee" size="30" :sub="$o->start_date->format('M d').' – '.$o->end_date->format('M d')" /></div>
            @empty
                <div class="p-4 text-center small text-muted"><i class="bi bi-people"></i> No overlapping leave in this department.</div>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
