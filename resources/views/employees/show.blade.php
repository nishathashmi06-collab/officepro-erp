@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')
@php($isSelf = auth()->user()->employee?->is($employee))
<x-breadcrumb :items="auth()->user()->can('viewAny', App\Models\Employee::class) ? ['Employees' => route('employees.index'), $employee->full_name => null] : ['My Profile' => null]" />

{{-- Profile header --}}
<div class="op-card mb-4 op-animate">
    <div class="op-profile-cover"></div>
    <div class="op-profile-head">
        <x-avatar :name="$employee->full_name" :src="$employee->photo_url" size="104" />
        <div class="flex-fill min-w-0 pt-5 pt-sm-0">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h1 class="h3 mb-0">{{ $employee->full_name }}</h1>
                <x-status-badge :status="$employee->status" />
            </div>
            <div class="text-muted mt-1 d-flex flex-wrap gap-3 small">
                <span><i class="bi bi-hash"></i> {{ $employee->employee_code }}</span>
                <span><i class="bi bi-diagram-3"></i> {{ $employee->department?->name ?? 'No department' }}</span>
                <span><i class="bi bi-person-badge"></i> {{ $employee->designation?->name ?? 'No designation' }}</span>
                <span><i class="bi bi-briefcase"></i> {{ label($employee->employment_type) }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($isSelf)
                <a href="{{ route('profile.edit') }}" class="btn btn-light"><i class="bi bi-sliders"></i> Account settings</a>
            @endif
            @can('tasks.create')
                @if (in_array($employee->id, auth()->user()->teamEmployeeIds()) || auth()->user()->can('tasks.manage_all'))
                    <a href="{{ route('tasks.create', ['employee' => $employee->id]) }}" class="btn btn-light"><i class="bi bi-plus-square"></i> Assign task</a>
                @endif
            @endcan
            @can('update', $employee)
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
            @can('delete', $employee)
                <div class="dropdown">
                    <button class="btn btn-light btn-icon" data-bs-toggle="dropdown" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if ($employee->status !== 'inactive')
                            <li>
                                <form method="POST" action="{{ route('employees.deactivate', $employee) }}" data-confirm="The employee will be marked inactive and their login disabled." data-confirm-title="Deactivate {{ $employee->first_name }}?" data-confirm-button="Deactivate">
                                    @csrf
                                    <button class="dropdown-item"><i class="bi bi-person-dash"></i> Deactivate</button>
                                </form>
                            </li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-confirm="The employee will be removed from lists. History such as payroll and attendance is preserved." data-confirm-title="Delete {{ $employee->full_name }}?">
                                @csrf @method('DELETE')
                                <button class="dropdown-item text-danger"><i class="bi bi-trash text-danger"></i> Delete</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endcan
        </div>
    </div>

    <ul class="nav op-tabs px-3" role="tablist">
        @php($tabs = ['overview' => ['Overview', 'bi-person'], 'attendance' => ['Attendance', 'bi-fingerprint'], 'leaves' => ['Leaves', 'bi-calendar2-check'], 'tasks' => ['Tasks', 'bi-kanban']] + ($canSensitive ? ['payroll' => ['Payroll', 'bi-cash-stack'], 'documents' => ['Documents', 'bi-folder2']] : []) + ['activity' => ['Activity', 'bi-activity']])
        @foreach ($tabs as $key => [$tabLabel, $icon])
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}" type="button" role="tab"><i class="bi {{ $icon }}"></i> {{ $tabLabel }}</button>
            </li>
        @endforeach
    </ul>
</div>

<div class="tab-content">
    {{-- Overview --}}
    <div class="tab-pane fade show active" id="pane-overview" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-7">
                <x-card title="Personal details" icon="bi-person-vcard" class="mb-4">
                    <dl class="op-dl">
                        <dt>Email</dt><dd><a href="mailto:{{ $employee->email }}">{{ $employee->email }}</a></dd>
                        <dt>Phone</dt><dd>{{ $employee->phone ?? '—' }}</dd>
                        <dt>Gender</dt><dd>{{ label($employee->gender) }}</dd>
                        @if ($canSensitive)
                            <dt>Date of birth</dt><dd>{{ fmt_date($employee->date_of_birth) }}@if($employee->date_of_birth) <span class="text-muted">({{ $employee->date_of_birth->age }} yrs)</span>@endif</dd>
                            <dt>Address</dt><dd>{{ $employee->address ?? '—' }}</dd>
                            <dt>Emergency contact</dt><dd>{{ $employee->emergency_contact_name ?? '—' }} @if($employee->emergency_contact_phone)<span class="text-muted">· {{ $employee->emergency_contact_phone }}</span>@endif</dd>
                        @endif
                    </dl>
                </x-card>
                <x-card title="Employment" icon="bi-briefcase">
                    <dl class="op-dl">
                        <dt>Employee ID</dt><dd class="font-monospace">{{ $employee->employee_code }}</dd>
                        <dt>Department</dt><dd>{{ $employee->department?->name ?? '—' }}</dd>
                        <dt>Reports to</dt><dd>{{ $employee->department?->manager && ! $employee->department->manager->is($employee) ? $employee->department->manager->full_name : '—' }}</dd>
                        <dt>Designation</dt><dd>{{ $employee->designation?->name ?? '—' }}</dd>
                        <dt>Employment type</dt><dd>{{ label($employee->employment_type) }}</dd>
                        <dt>Joining date</dt><dd>{{ fmt_date($employee->joining_date) }} <span class="text-muted">({{ $employee->joining_date->diffForHumans(null, true) }})</span></dd>
                        @if ($canSensitive)
                            <dt>Monthly salary</dt><dd>{{ money($employee->salary) }}</dd>
                        @endif
                        <dt>Login account</dt><dd>{{ $employee->user ? $employee->user->email.' · '.$employee->user->role?->name : 'No login' }}</dd>
                        @if ($employee->notes && auth()->user()->can('update', $employee))
                            <dt>Notes</dt><dd class="fw-normal">{{ $employee->notes }}</dd>
                        @endif
                    </dl>
                </x-card>
            </div>
            <div class="col-lg-5">
                <x-card title="This month" icon="bi-calendar3" class="mb-4">
                    <div class="row g-3 text-center">
                        @foreach (['present' => 'success', 'late' => 'warning', 'absent' => 'danger', 'leave' => 'purple'] as $st => $color)
                            <div class="col-3">
                                <div class="h4 mb-0">{{ (int) ($attendanceSummary[$st]->total ?? 0) }}</div>
                                <div class="small"><span class="op-badge op-soft-{{ $color }}">{{ label($st) }}</span></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center text-muted small mt-3">{{ number_format((float) $attendanceSummary->sum('hours'), 1) }} hours worked this month</div>
                </x-card>
                <x-card title="Leave balance {{ now()->year }}" icon="bi-umbrella" class="mb-4">
                    @foreach ($leaveBalances as $b)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold">{{ $b->type->name }}</span>
                                <span class="text-muted">{{ $b->remaining === null ? $b->used.' used (unlimited)' : $b->remaining.' of '.$b->allowed.' left' }}</span>
                            </div>
                            @if ($b->allowed > 0)
                                <x-progress :value="$b->allowed ? ($b->used + $b->pending) / $b->allowed * 100 : 0" variant="info" :show-label="false" />
                            @endif
                        </div>
                    @endforeach
                </x-card>
                <x-card title="Assigned assets" icon="bi-laptop" :padding="false">
                    @forelse ($employee->assets as $asset)
                        <div class="op-list-item">
                            <span class="op-list-icon op-soft-info"><i class="bi bi-laptop"></i></span>
                            <div class="min-w-0 flex-fill">
                                <div class="fw-semibold">{{ $asset->name }}</div>
                                <div class="small text-muted">{{ $asset->asset_code }} · {{ $asset->serial_number ?? 'No serial' }}</div>
                            </div>
                            @can('assets.view')<a href="{{ route('assets.show', $asset) }}" class="btn btn-light btn-sm">View</a>@endcan
                        </div>
                    @empty
                        <x-empty-state icon="bi-laptop" title="No assets assigned" />
                    @endforelse
                </x-card>
            </div>
        </div>
    </div>

    {{-- Attendance --}}
    <div class="tab-pane fade" id="pane-attendance" role="tabpanel">
        <x-card title="Recent attendance" subtitle="Last 31 records" :padding="false">
            <x-slot:actions><a href="{{ route('attendance.index', ['employee' => $employee->id]) }}" class="btn btn-light btn-sm">Full history</a></x-slot:actions>
            @if ($attendances->isEmpty())
                <x-empty-state icon="bi-fingerprint" title="No attendance records" />
            @else
                <x-table>
                    <thead><tr><th>Date</th><th>Check in</th><th>Check out</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($attendances as $a)
                            <tr>
                                <td data-label="Date">{{ fmt_date($a->date) }} <span class="text-muted small">{{ $a->date->format('D') }}</span></td>
                                <td data-label="Check in">{{ fmt_time($a->check_in) }}</td>
                                <td data-label="Check out">{{ fmt_time($a->check_out) }}</td>
                                <td data-label="Hours">{{ number_format((float) $a->working_hours, 2) }}</td>
                                <td data-label="Status"><x-status-badge :status="$a->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    {{-- Leaves --}}
    <div class="tab-pane fade" id="pane-leaves" role="tabpanel">
        <x-card title="Leave history" :padding="false">
            @if ($isSelf)
            <x-slot:actions><a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Request leave</a></x-slot:actions>
            @endif
            @if ($leaves->isEmpty())
                <x-empty-state icon="bi-calendar2" title="No leave requests" />
            @else
                <x-table>
                    <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($leaves as $leave)
                            <tr data-href="{{ route('leaves.show', $leave) }}">
                                <td data-label="Type" class="fw-semibold">{{ $leave->leaveType->name }}</td>
                                <td data-label="Dates">{{ fmt_date($leave->start_date) }} – {{ fmt_date($leave->end_date) }}</td>
                                <td data-label="Days">{{ rtrim(rtrim((string) $leave->days, '0'), '.') }}</td>
                                <td data-label="Status"><x-status-badge :status="$leave->status" /></td>
                                <td class="actions" data-label=""><a href="{{ route('leaves.show', $leave) }}" class="btn btn-light btn-sm">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    {{-- Tasks --}}
    <div class="tab-pane fade" id="pane-tasks" role="tabpanel">
        <x-card title="Assigned tasks" :padding="false">
            @if ($tasks->isEmpty())
                <x-empty-state icon="bi-kanban" title="No tasks assigned" />
            @else
                <x-table>
                    <thead><tr><th>Task</th><th>Priority</th><th>Due</th><th style="min-width:140px">Progress</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr data-href="{{ route('tasks.show', $task) }}">
                                <td data-label="Task"><a href="{{ route('tasks.show', $task) }}" class="fw-semibold">{{ $task->title }}</a><div class="small text-muted">by {{ $task->creator?->name ?? '—' }}</div></td>
                                <td data-label="Priority"><x-status-badge :status="$task->priority" /></td>
                                <td data-label="Due" class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ fmt_date($task->due_date) }}</td>
                                <td data-label="Progress"><x-progress :value="$task->progress" /></td>
                                <td data-label="Status"><x-status-badge :status="$task->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    @if ($canSensitive)
        {{-- Payroll --}}
        <div class="tab-pane fade" id="pane-payroll" role="tabpanel">
            <x-card title="Salary history" :padding="false">
                @if ($payrolls->isEmpty())
                    <x-empty-state icon="bi-cash-stack" title="No payroll records" message="Salary slips appear here once payroll is approved." />
                @else
                    <x-table>
                        <thead><tr><th>Month</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($payrolls as $p)
                                <tr>
                                    <td data-label="Month" class="fw-semibold">{{ $p->period_label }}</td>
                                    <td data-label="Gross" class="text-end">{{ money($p->gross_salary) }}</td>
                                    <td data-label="Deductions" class="text-end text-danger">-{{ money($p->total_deductions) }}</td>
                                    <td data-label="Net" class="text-end fw-bold">{{ money($p->net_salary) }}</td>
                                    <td data-label="Status"><x-status-badge :status="$p->status" /></td>
                                    <td class="actions" data-label="">
                                        @can('view', $p)
                                            <a href="{{ route('payroll.show', $p) }}" class="btn btn-light btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('payroll.slip', [$p, 'download' => 1]) }}" class="btn btn-soft-primary btn-sm btn-icon" title="Download slip"><i class="bi bi-file-earmark-pdf"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        {{-- Documents --}}
        <div class="tab-pane fade" id="pane-documents" role="tabpanel">
            <x-card title="Documents" :padding="false">
                @can('documents.manage')
                    <x-slot:actions><a href="{{ route('documents.create', ['employee' => $employee->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Upload</a></x-slot:actions>
                @endcan
                @forelse ($documents as $doc)
                    @php($state = $doc->expiryState())
                    <div class="op-list-item">
                        <span class="op-list-icon op-soft-primary"><i class="bi bi-file-earmark-text"></i></span>
                        <div class="min-w-0 flex-fill">
                            <div class="fw-semibold text-truncate">{{ $doc->title }}</div>
                            <div class="small text-muted">{{ label($doc->category) }} · {{ $doc->readable_size }} · Expires {{ fmt_date($doc->expiry_date) }}</div>
                        </div>
                        @if ($state && $state !== 'valid')<x-status-badge :status="$state" />@endif
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-light btn-sm btn-icon" title="Download"><i class="bi bi-download"></i></a>
                    </div>
                @empty
                    <x-empty-state icon="bi-folder2" title="No documents" />
                @endforelse
            </x-card>
        </div>
    @endif

    {{-- Activity --}}
    <div class="tab-pane fade" id="pane-activity" role="tabpanel">
        <x-card title="Activity" subtitle="Changes to this profile and actions by this employee">
            @if ($activity->isEmpty())
                <x-empty-state icon="bi-activity" title="No activity yet" />
            @else
                <div class="op-timeline">
                    @foreach ($activity as $log)
                        <div class="op-timeline-item">
                            <span class="dot"><i class="bi {{ $log->icon() }}"></i></span>
                            <div class="text"><strong>{{ $log->user?->name ?? 'System' }}</strong> · {{ $log->description }}</div>
                            <time>{{ $log->created_at->format('M d, Y h:i A') }} · {{ label($log->module) }}</time>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection
