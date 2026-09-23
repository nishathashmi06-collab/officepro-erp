@extends('layouts.app')
@section('title', 'Attendance')
@section('content')
<x-page-header title="Attendance" :subtitle="$canFilterPeople ? 'Track check-ins, working hours and absences.' : 'Your check-ins and working hours.'" :breadcrumbs="['Attendance' => null]">
    <x-slot:actions>
        @can('reports.view')
            <a href="{{ route('reports.show', ['attendance', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="btn btn-light"><i class="bi bi-bar-chart-line"></i> Report</a>
        @endcan
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('attendance.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add record</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @if ($employee)
        <div class="col-xl-4 op-animate">
            <div class="op-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="small-caps mb-1">Today · {{ now()->format('D, M d') }}</div>
                    <div class="display-6 fw-bold op-clock" data-clock style="letter-spacing:-.03em"></div>
                    @if ($today)
                        <div class="mt-2 small">
                            <x-status-badge :status="$today->status" />
                            <span class="text-muted ms-1">In {{ fmt_time($today->check_in) }} @if($today->check_out) · Out {{ fmt_time($today->check_out) }} · {{ number_format((float) $today->working_hours, 2) }} h @endif</span>
                        </div>
                    @else
                        <div class="mt-2 small text-muted">Not checked in yet · workday starts {{ fmt_time(setting('work_start_time')) }}</div>
                    @endif
                </div>
                <div class="mt-3">
                    @if (! $today)
                        <form method="POST" action="{{ route('attendance.check-in') }}">@csrf<button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Check in</button></form>
                    @elseif ($today->check_in && ! $today->check_out)
                        <form method="POST" action="{{ route('attendance.check-out') }}">@csrf<button class="btn btn-soft-danger btn-lg w-100"><i class="bi bi-box-arrow-right"></i> Check out</button></form>
                    @else
                        <div class="op-alert op-alert-success mb-0"><i class="bi bi-check-circle-fill lead-icon"></i><div>Attendance for today is complete.</div></div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    <div class="{{ $employee ? 'col-xl-8' : 'col-12' }}">
        <div class="row g-3 h-100">
            @foreach (['present' => ['Present', 'bi-check-circle', 'success'], 'late' => ['Late', 'bi-alarm', 'warning'], 'half_day' => ['Half day', 'bi-circle-half', 'info'], 'absent' => ['Absent', 'bi-x-circle', 'danger'], 'leave' => ['On leave', 'bi-umbrella', 'purple']] as $st => [$lbl, $icon, $color])
                <div class="col-6 col-md-4 op-animate op-animate-{{ $loop->iteration }}"><x-stat-card :label="$lbl" :value="(int) ($summary[$st] ?? 0)" :icon="$icon" :color="$color" meta="In selected period" /></div>
            @endforeach
            <div class="col-6 col-md-4 op-animate op-animate-6"><x-stat-card label="Hours worked" :value="number_format($totalHours, 1)" icon="bi-clock-history" color="primary" meta="In selected period" /></div>
        </div>
    </div>
</div>

<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom op-filters">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <div class="op-segmented">
                @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Custom'] as $key => $lbl)
                    <a href="{{ request()->fullUrlWithQuery(['period' => $key, 'page' => null]) }}" class="{{ $period === $key ? 'active' : '' }}">{{ $lbl }}</a>
                @endforeach
            </div>
            <span class="text-muted small ms-auto"><i class="bi bi-calendar-range"></i> {{ fmt_date($from) }} – {{ fmt_date($to) }}</span>
        </div>
        <input type="hidden" name="period" value="{{ $period }}">
        <div class="row g-2 align-items-end">
            @if ($period === 'custom')
                <div class="col-md-2 col-6"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
                <div class="col-md-2 col-6"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
            @endif
            @if ($canFilterPeople)
                <div class="col-md-3 col-6">
                    <label class="form-label small">Employee</label>
                    <select name="employee" class="form-select"><option value="">All employees</option>
                        @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('employee') == $e->id)>{{ $e->full_name }}</option>@endforeach
                    </select>
                </div>
            @endif
            @if ($departments->isNotEmpty())
                <div class="col-md-2 col-6">
                    <label class="form-label small">Department</label>
                    <select name="department" class="form-select"><option value="">All</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2 col-6">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select"><option value="">All</option>
                    @foreach (App\Models\Attendance::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-1 col-6"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
        </div>
    </form>

    @if ($records->isEmpty())
        <x-empty-state icon="bi-fingerprint" title="No attendance records" message="No records match the selected period and filters." />
    @else
        <x-table>
            <thead><tr>
                <th>Date</th>
                @if ($canFilterPeople)<th>Employee</th>@endif
                <th>Check in</th><th>Check out</th><th>Hours</th><th>Status</th><th>Notes</th>
                @can('attendance.manage')<th class="text-end">Actions</th>@endcan
            </tr></thead>
            <tbody>
            @foreach ($records as $r)
                <tr>
                    <td data-label="Date"><div class="fw-semibold">{{ fmt_date($r->date) }}</div><div class="small text-muted">{{ $r->date->format('l') }}</div></td>
                    @if ($canFilterPeople)<td data-label=""><x-person :employee="$r->employee" :href="route('employees.show', $r->employee_id)" :sub="$r->employee?->department?->name" size="32" /></td>@endif
                    <td data-label="Check in">{{ fmt_time($r->check_in) }}</td>
                    <td data-label="Check out">{{ fmt_time($r->check_out) }}</td>
                    <td data-label="Hours" class="fw-semibold">{{ number_format((float) $r->working_hours, 2) }}</td>
                    <td data-label="Status"><x-status-badge :status="$r->status" /></td>
                    <td data-label="Notes" class="small text-muted">{{ Str::limit($r->notes, 40) ?: '—' }}</td>
                    @can('attendance.manage')
                        <td class="actions" data-label="">
                            <a href="{{ route('attendance.edit', $r) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                            <x-delete-button :action="route('attendance.destroy', $r)" title="Delete this attendance record?" />
                        </td>
                    @endcan
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $records->links() }}
    @endif
</x-card>
@endsection
