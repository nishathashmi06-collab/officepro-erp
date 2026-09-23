@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $firstName = $employee?->first_name ?? Str::before(auth()->user()->name, ' ');
    $pct = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100) : 0;
@endphp

{{-- Hero --}}
<div class="op-hero mb-4 op-animate">
    <div class="row align-items-center g-3 position-relative" style="z-index:1">
        <div class="col-lg-7">
            <div class="small text-white-50 mb-1"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }} · <span class="op-clock" data-clock></span></div>
            <h1 class="h2 mb-1">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="mb-0">Here's what's happening in your office today.</p>
        </div>
        <div class="col-lg-5 text-lg-end">
            @if ($employee)
                @if (! $todayAttendance)
                    <form method="POST" action="{{ route('attendance.check-in') }}" class="d-inline">
                        @csrf
                        <button class="btn btn-light btn-lg"><i class="bi bi-box-arrow-in-right"></i> Check in now</button>
                    </form>
                    <div class="small text-white-50 mt-2">Workday starts at {{ fmt_time(setting('work_start_time')) }}</div>
                @elseif ($todayAttendance->check_in && ! $todayAttendance->check_out)
                    <form method="POST" action="{{ route('attendance.check-out') }}" class="d-inline" data-confirm="You will be checked out for today." data-confirm-title="Check out now?" data-confirm-button="Check out" data-confirm-variant="primary">
                        @csrf
                        <button class="btn btn-light btn-lg"><i class="bi bi-box-arrow-right"></i> Check out</button>
                    </form>
                    <div class="small text-white-50 mt-2">Checked in at {{ fmt_time($todayAttendance->check_in) }} · <x-status-badge :status="$todayAttendance->status" class="bg-white" /></div>
                @else
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background: rgba(255,255,255,.14)">
                        <i class="bi bi-check2-circle fs-4"></i>
                        <div class="text-start small">
                            <div class="fw-semibold">Attendance recorded — {{ label($todayAttendance->status) }}</div>
                            <div class="text-white-50">{{ fmt_time($todayAttendance->check_in) }} – {{ fmt_time($todayAttendance->check_out) }} · {{ number_format((float) $todayAttendance->working_hours, 2) }} h</div>
                        </div>
                    </div>
                @endif
            @else
                <a href="{{ route('reports.index') }}" class="btn btn-glass"><i class="bi bi-bar-chart-line"></i> View reports</a>
            @endif
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    @if ($orgView)
        <div class="col-6 col-xl-3 op-animate op-animate-1"><x-stat-card label="Total Employees" :value="number_format($stats['total_employees'])" icon="bi-people" color="primary" :meta="$stats['active_employees'].' active'" :href="route('employees.index')" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-2"><x-stat-card label="Active Employees" :value="number_format($stats['active_employees'])" icon="bi-person-check" color="success" :meta="$pct($stats['active_employees'], $stats['total_employees']).'% of workforce'" :href="route('employees.index', ['status' => 'active'])" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-3"><x-stat-card label="Present Today" :value="$stats['present_today']" icon="bi-fingerprint" color="info" :meta="$stats['late_today'].' late · '.$pct($stats['present_today'], $stats['active_employees']).'% attendance'" :href="route('attendance.index', ['period' => 'today'])" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-4"><x-stat-card label="Absent Today" :value="$stats['absent_today']" icon="bi-person-x" color="danger" :meta="$stats['on_leave_today'].' on approved leave'" :href="route('attendance.index', ['period' => 'today'])" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-3"><x-stat-card label="Pending Leaves" :value="$stats['pending_leaves']" icon="bi-calendar2-event" color="warning" meta="Awaiting review" :href="route('leaves.index', ['status' => 'pending'])" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-4"><x-stat-card label="Pending Tasks" :value="$stats['pending_tasks']" icon="bi-kanban" color="purple" meta="Open across teams" :href="route('tasks.index')" /></div>
        @if (! is_null($stats['monthly_payroll']))
            <div class="col-6 col-xl-3 op-animate op-animate-5"><x-stat-card label="Monthly Payroll" :value="money($stats['monthly_payroll'])" icon="bi-cash-stack" color="success" :meta="now()->format('F Y').' · net'" :href="route('payroll.index', ['month' => now()->format('Y-m')])" /></div>
        @endif
        @if (! is_null($stats['monthly_expenses']))
            <div class="col-6 col-xl-3 op-animate op-animate-6"><x-stat-card label="Monthly Expenses" :value="money($stats['monthly_expenses'])" icon="bi-receipt" color="pink" :meta="now()->format('F Y').' · approved'" :href="route('expenses.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()])" /></div>
        @endif
    @else
        @php($annual = $leaveBalances->first(fn ($b) => $b->remaining !== null))
        <div class="col-6 col-xl-3 op-animate op-animate-1"><x-stat-card label="Days Present (this month)" :value="(int) ($myMonth->days ?? 0)" icon="bi-calendar-check" color="success" :meta="(int) ($myMonth->late ?? 0).' late arrival(s)'" :href="route('attendance.index')" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-2"><x-stat-card label="Hours Worked (this month)" :value="number_format((float) ($myMonth->hours ?? 0), 1)" icon="bi-clock-history" color="info" meta="From check-in / check-out" :href="route('attendance.index')" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-3"><x-stat-card label="My Open Tasks" :value="$stats['pending_tasks']" icon="bi-kanban" color="purple" :meta="$upcomingTasks->count().' due in the next 14 days'" :href="route('tasks.index', ['mine' => 1])" /></div>
        <div class="col-6 col-xl-3 op-animate op-animate-4"><x-stat-card label="Leave Balance" :value="$annual ? rtrim(rtrim(number_format($annual->remaining, 1), '0'), '.').' days' : '—'" icon="bi-umbrella" color="warning" :meta="$annual ? $annual->type->name.' remaining' : 'No leave types'" :href="route('leaves.index')" /></div>
    @endif
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
    <div class="{{ $charts['departments'] ? 'col-xl-8' : 'col-12' }} op-animate op-animate-2">
        <x-card title="Attendance overview" subtitle="Last 7 working days" icon="bi-bar-chart" class="h-100">
            <x-slot:actions><a href="{{ route('attendance.index') }}" class="btn btn-light btn-sm">Details</a></x-slot:actions>
            <x-chart :config="['type' => 'bar', 'stacked' => true, 'labels' => $charts['attendance']['labels'], 'datasets' => [
                ['label' => 'Present', 'data' => $charts['attendance']['series']['present'], 'color' => 'success'],
                ['label' => 'Late', 'data' => $charts['attendance']['series']['late'], 'color' => 'warning'],
                ['label' => 'Half day', 'data' => $charts['attendance']['series']['half_day'], 'color' => 'info'],
                ['label' => 'Absent', 'data' => $charts['attendance']['series']['absent'], 'color' => 'danger'],
                ['label' => 'Leave', 'data' => $charts['attendance']['series']['leave'], 'color' => 'purple'],
            ]]" />
        </x-card>
    </div>
    @if ($charts['departments'])
        <div class="col-xl-4 op-animate op-animate-3">
            <x-card title="Employees by department" subtitle="Active headcount" icon="bi-diagram-3" class="h-100">
                @if (count($charts['departments']['data']))
                    <x-chart :config="['type' => 'doughnut', 'labels' => $charts['departments']['labels'], 'datasets' => [['label' => 'Employees', 'data' => $charts['departments']['data']]]]" />
                @else
                    <x-empty-state icon="bi-diagram-3" title="No departments yet" />
                @endif
            </x-card>
        </div>
    @endif

    @if ($charts['expenses'])
        <div class="col-xl-6 op-animate op-animate-3">
            <x-card title="Monthly expenses" subtitle="Approved spend, last 6 months" icon="bi-graph-up" class="h-100">
                <x-chart :config="['type' => 'line', 'money' => true, 'legend' => false, 'labels' => $charts['expenses']['labels'], 'datasets' => [['label' => 'Expenses', 'data' => $charts['expenses']['data'], 'color' => 'pink']]]" />
            </x-card>
        </div>
    @endif
    @if ($charts['payroll'])
        <div class="col-xl-6 op-animate op-animate-4">
            <x-card title="Payroll summary" subtitle="Gross vs net pay, last 6 months" icon="bi-cash-coin" class="h-100">
                <x-chart :config="['type' => 'bar', 'money' => true, 'labels' => $charts['payroll']['labels'], 'datasets' => [
                    ['label' => 'Gross', 'data' => $charts['payroll']['gross'], 'color' => 'primary'],
                    ['label' => 'Net', 'data' => $charts['payroll']['net'], 'color' => 'success'],
                ]]" />
            </x-card>
        </div>
    @endif

    <div class="col-xl-5 op-animate op-animate-4">
        <x-card title="Task completion" subtitle="Tasks by status" icon="bi-pie-chart" class="h-100">
            @if (array_sum($charts['tasks']['data']))
                <x-chart class="sm" :config="['type' => 'doughnut', 'labels' => $charts['tasks']['labels'], 'datasets' => [['label' => 'Tasks', 'data' => $charts['tasks']['data'], 'colors' => ['warning', 'primary', 'purple', 'success', 'secondary']]]]" />
                @php($totalTasks = array_sum($charts['tasks']['data']))
                <div class="text-center small text-muted mt-2"><strong class="text-body">{{ $pct($charts['tasks']['data'][3], $totalTasks) }}%</strong> of {{ $totalTasks }} tasks completed</div>
            @else
                <x-empty-state icon="bi-kanban" title="No tasks yet" />
            @endif
        </x-card>
    </div>
    <div class="col-xl-7 op-animate op-animate-5">
        <x-card title="Leave statistics" :subtitle="'Days requested by type, '.now()->year" icon="bi-calendar2-range" class="h-100">
            <x-chart class="sm" :config="['type' => 'bar', 'stacked' => true, 'labels' => $charts['leaves']['labels'], 'datasets' => [
                ['label' => 'Approved', 'data' => $charts['leaves']['series']['approved'], 'color' => 'success'],
                ['label' => 'Pending', 'data' => $charts['leaves']['series']['pending'], 'color' => 'warning'],
                ['label' => 'Rejected', 'data' => $charts['leaves']['series']['rejected'], 'color' => 'danger'],
            ]]" />
        </x-card>
    </div>
</div>

{{-- Lists --}}
<div class="row g-3">
    <div class="col-xl-4 col-lg-6 op-animate op-animate-3">
        <x-card title="Pending leave requests" icon="bi-hourglass-split" :padding="false" class="h-100">
            <x-slot:actions><a href="{{ route('leaves.index', ['status' => 'pending']) }}" class="small fw-semibold">View all</a></x-slot:actions>
            @forelse ($pendingLeaves as $leave)
                <a href="{{ route('leaves.show', $leave) }}" class="op-list-item">
                    <x-avatar :name="$leave->employee->full_name" :src="$leave->employee->photo_url" size="38" />
                    <div class="min-w-0 flex-fill">
                        <div class="fw-semibold text-truncate">{{ $leave->employee->full_name }}</div>
                        <div class="small text-muted">{{ $leave->leaveType->name }} · {{ $leave->start_date->format('M d') }}–{{ $leave->end_date->format('M d') }}</div>
                    </div>
                    <span class="op-badge op-soft-warning no-dot">{{ rtrim(rtrim((string) $leave->days, '0'), '.') }}d</span>
                </a>
            @empty
                <x-empty-state icon="bi-check2-all" title="All clear" message="No leave requests waiting for review." />
            @endforelse
        </x-card>
    </div>

    <div class="col-xl-4 col-lg-6 op-animate op-animate-4">
        <x-card title="Upcoming task deadlines" icon="bi-alarm" :padding="false" class="h-100">
            <x-slot:actions><a href="{{ route('tasks.board') }}" class="small fw-semibold">Board</a></x-slot:actions>
            @forelse ($upcomingTasks as $task)
                <a href="{{ route('tasks.show', $task) }}" class="op-list-item">
                    <span class="op-list-icon op-soft-{{ $task->isOverdue() ? 'danger' : ($task->due_date->isToday() ? 'warning' : 'primary') }}"><i class="bi bi-flag"></i></span>
                    <div class="min-w-0 flex-fill">
                        <div class="fw-semibold text-truncate">{{ $task->title }}</div>
                        <div class="small text-muted text-truncate">{{ $task->assignee?->full_name ?? 'Unassigned' }} · <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $task->isOverdue() ? 'Overdue · ' : '' }}{{ $task->due_date->format('M d') }}</span></div>
                    </div>
                    <x-status-badge :status="$task->priority" />
                </a>
            @empty
                <x-empty-state icon="bi-calendar2-check" title="No upcoming deadlines" message="Nothing due in the next two weeks." />
            @endforelse
        </x-card>
    </div>

    <div class="col-xl-4 col-lg-6 op-animate op-animate-5">
        <x-card title="Upcoming birthdays" icon="bi-cake2" :padding="false" class="h-100">
            @forelse ($birthdays as $person)
                <div class="op-list-item">
                    <x-avatar :name="$person->full_name" :src="$person->photo_url" size="38" />
                    <div class="min-w-0 flex-fill">
                        <div class="fw-semibold text-truncate">{{ $person->full_name }}</div>
                        <div class="small text-muted">{{ $person->department?->name ?? '—' }} · {{ $person->next_birthday->format('M d') }}</div>
                    </div>
                    <span class="op-badge {{ $person->days_until_birthday === 0 ? 'op-soft-pink' : 'op-soft-secondary' }} no-dot">
                        {{ $person->days_until_birthday === 0 ? '🎉 Today' : 'in '.$person->days_until_birthday.'d' }}
                    </span>
                </div>
            @empty
                <x-empty-state icon="bi-balloon" title="No birthdays soon" message="No birthdays in the next 30 days." />
            @endforelse
        </x-card>
    </div>

    <div class="col-xl-5 col-lg-6 op-animate op-animate-4">
        <x-card title="Expiring documents" :subtitle="'Within '.setting('document_expiry_days', 30).' days or already expired'" icon="bi-file-earmark-excel" :padding="false" class="h-100">
            <x-slot:actions><a href="{{ route('documents.index', ['expiry' => 'expiring']) }}" class="small fw-semibold">View all</a></x-slot:actions>
            @forelse ($expiringDocuments as $doc)
                @php($state = $doc->expiryState())
                <a href="{{ route('documents.download', [$doc, 'inline' => 1]) }}" class="op-list-item" target="_blank" rel="noopener">
                    <span class="op-list-icon op-soft-{{ $state === 'expired' ? 'danger' : 'warning' }}"><i class="bi bi-file-earmark-text"></i></span>
                    <div class="min-w-0 flex-fill">
                        <div class="fw-semibold text-truncate">{{ $doc->title }}</div>
                        <div class="small text-muted text-truncate">{{ $doc->employee?->full_name ?? 'Company-wide' }} · {{ fmt_date($doc->expiry_date) }}</div>
                    </div>
                    <x-status-badge :status="$state" />
                </a>
            @empty
                <x-empty-state icon="bi-shield-check" title="Nothing expiring" message="No documents are close to their expiry date." />
            @endforelse
        </x-card>
    </div>

    <div class="col-xl-7 col-lg-12 op-animate op-animate-5">
        <x-card title="Recent activities" icon="bi-activity" class="h-100">
            @can('activity_logs.view')
                <x-slot:actions><a href="{{ route('activity-logs.index') }}" class="small fw-semibold">Activity log</a></x-slot:actions>
            @endcan
            @if ($recentActivities->isNotEmpty())
                <div class="op-timeline">
                    @foreach ($recentActivities as $log)
                        <div class="op-timeline-item">
                            <span class="dot"><i class="bi {{ $log->icon() }}"></i></span>
                            <div class="text"><strong>{{ $log->user?->name ?? 'System' }}</strong> · {{ $log->description }}</div>
                            <time title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }} · {{ label($log->module) }}</time>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state icon="bi-activity" title="No activity yet" />
            @endif
        </x-card>
    </div>
</div>
@endsection
