@extends('layouts.app')
@section('title', 'Leave Management')
@section('content')
<x-page-header title="Leave Management" :subtitle="$canSeeOthers ? 'Review requests and track leave across the team.' : 'Request time off and follow your balance.'" :breadcrumbs="['Leave' => null]">
    <x-slot:actions>
        @can('leave_types.manage')<a href="{{ route('leave-types.index') }}" class="btn btn-light"><i class="bi bi-sliders2"></i> Leave types</a>@endcan
        <a href="{{ route('leaves.create') }}" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Request leave</a>
    </x-slot:actions>
</x-page-header>

@if ($balances->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach ($balances as $b)
            <div class="col-6 col-lg op-animate op-animate-{{ $loop->iteration }}">
                <div class="op-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold">{{ $b->type->name }}</span>
                        <span class="op-badge op-soft-{{ $b->type->color === 'secondary' ? 'secondary' : $b->type->color }} no-dot">{{ $b->type->code }}</span>
                    </div>
                    <div class="h4 mb-0">{{ $b->remaining === null ? '∞' : rtrim(rtrim(number_format($b->remaining, 1), '0'), '.') }} <span class="small text-muted fw-normal">{{ $b->remaining === null ? 'unpaid' : 'of '.$b->allowed.' left' }}</span></div>
                    <div class="small text-muted mb-2">{{ (float) $b->used }} used · {{ (float) $b->pending }} pending</div>
                    @if ($b->allowed)<x-progress :value="($b->used + $b->pending) / $b->allowed * 100" :show-label="false" />@endif
                </div>
            </div>
        @endforeach
    </div>
@endif

<x-card :padding="false">
    <div class="op-card-body border-bottom op-filters">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="op-segmented">
                <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="{{ ! request('status') ? 'active' : '' }}">All <span class="text-muted">{{ $counts->sum() }}</span></a>
                @foreach (App\Models\Leave::STATUSES as $s)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $s, 'page' => null]) }}" class="{{ request('status') === $s ? 'active' : '' }}">{{ label($s) }} <span class="text-muted">{{ $counts[$s] ?? 0 }}</span></a>
                @endforeach
            </div>
            @if ($canSeeOthers && auth()->user()->employee)
                <div class="op-segmented ms-md-auto">
                    <a href="{{ request()->fullUrlWithQuery(['scope' => null]) }}" class="{{ request('scope') !== 'mine' ? 'active' : '' }}">Everyone</a>
                    <a href="{{ request()->fullUrlWithQuery(['scope' => 'mine']) }}" class="{{ request('scope') === 'mine' ? 'active' : '' }}">Mine</a>
                </div>
            @endif
        </div>
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="status" value="{{ request('status') }}"><input type="hidden" name="scope" value="{{ request('scope') }}">
            @if ($canSeeOthers)
                <div class="col-md-3 col-6"><label class="form-label small">Employee</label>
                    <select name="employee" class="form-select"><option value="">All</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('employee') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-2 col-6"><label class="form-label small">Type</label>
                <select name="type" class="form-select"><option value="">All</option>@foreach ($types as $t)<option value="{{ $t->id }}" @selected(request('type') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-2 col-6"><label class="form-label small">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2 col-6"><label class="form-label small">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-1 col-6"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
        </form>
    </div>

    @if ($leaves->isEmpty())
        <x-empty-state icon="bi-calendar2-check" title="No leave requests" message="Nothing matches your filters.">
            <a href="{{ route('leaves.create') }}" class="btn btn-primary">Request leave</a>
        </x-empty-state>
    @else
        <x-table>
            <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($leaves as $leave)
                <tr data-href="{{ route('leaves.show', $leave) }}">
                    <td data-label=""><x-person :employee="$leave->employee" :sub="$leave->employee?->department?->name" size="32" /></td>
                    <td data-label="Type" class="fw-semibold">{{ $leave->leaveType->name }}</td>
                    <td data-label="Dates">{{ fmt_date($leave->start_date) }} <span class="text-muted">→</span> {{ fmt_date($leave->end_date) }}<div class="small text-muted">Requested {{ $leave->created_at->diffForHumans() }}</div></td>
                    <td data-label="Days">{{ rtrim(rtrim((string) $leave->days, '0'), '.') }}</td>
                    <td data-label="Status"><x-status-badge :status="$leave->status" />@if($leave->reviewer)<div class="small text-muted mt-1">by {{ $leave->reviewer->name }}</div>@endif</td>
                    <td class="actions" data-label="">
                        @can('review', $leave)
                            <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="d-inline">@csrf<button class="btn btn-soft-success btn-sm" title="Approve"><i class="bi bi-check-lg"></i><span class="d-none d-xl-inline">Approve</span></button></form>
                        @endcan
                        <a href="{{ route('leaves.show', $leave) }}" class="btn btn-light btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $leaves->links() }}
    @endif
</x-card>
@endsection
