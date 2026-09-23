@php
    $u = $authUser;
    $isStaffView = $u->can('viewAny', \App\Models\Employee::class);
    $nav = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'bi-grid-1x2', 'dashboard', true],
        ],
        'People' => [
            [$isStaffView ? 'Employees' : 'My Profile', 'employees.index', 'bi-people', 'employees.*', true],
            ['Departments', 'departments.index', 'bi-diagram-3', 'departments.*', $u->can('departments.manage')],
            ['Designations', 'designations.index', 'bi-person-badge', 'designations.*', $u->can('designations.manage')],
        ],
        'Time & Work' => [
            ['Attendance', 'attendance.index', 'bi-fingerprint', 'attendance.*', true],
            ['Leave Management', 'leaves.index', 'bi-calendar2-check', 'leaves.*', true],
            ['Leave Types', 'leave-types.index', 'bi-sliders2', 'leave-types.*', $u->can('leave_types.manage')],
            ['Tasks', 'tasks.index', 'bi-kanban', 'tasks.*', true],
        ],
        'Finance' => [
            [$u->can('payroll.view_all') ? 'Payroll' : 'My Payslips', 'payroll.index', 'bi-cash-stack', 'payroll.*', true],
            ['Expenses', 'expenses.index', 'bi-receipt', 'expenses.*', $u->can('viewAny', \App\Models\Expense::class)],
            ['Expense Categories', 'expense-categories.index', 'bi-tags', 'expense-categories.*', $u->can('expenses.manage')],
        ],
        'Resources' => [
            ['Assets', 'assets.index', 'bi-laptop', 'assets.*', $u->can('assets.view')],
            ['Documents', 'documents.index', 'bi-folder2-open', 'documents.*', true],
        ],
        'Insights' => [
            ['Reports', 'reports.index', 'bi-bar-chart-line', 'reports.*', $u->can('reports.view')],
            ['Activity Log', 'activity-logs.index', 'bi-activity', 'activity-logs.*', $u->can('activity_logs.view')],
        ],
        'System' => [
            ['Notifications', 'notifications.index', 'bi-bell', 'notifications.*', true, $unreadNotificationCount],
            ['Users', 'users.index', 'bi-person-gear', 'users.*', $u->can('users.manage')],
            ['Roles & Permissions', 'roles.index', 'bi-shield-lock', 'roles.*', $u->can('roles.manage')],
            ['Settings', $u->can('settings.manage') ? 'settings.edit' : 'profile.edit', 'bi-gear', $u->can('settings.manage') ? 'settings.*' : 'profile.edit', true],
        ],
    ];
@endphp
<aside class="op-sidebar" id="sidebar" aria-label="Main navigation">
    <a href="{{ route('dashboard') }}" class="op-brand">
        <span class="op-brand-logo">
            @if (setting('company_logo'))
                <img src="{{ Storage::disk('public')->url(setting('company_logo')) }}" alt="">
            @else
                <i class="bi bi-briefcase-fill"></i>
            @endif
        </span>
        <span class="op-brand-text">OfficePro<small>{{ Str::limit(setting('company_name'), 26) }}</small></span>
    </a>

    <nav class="op-nav">
        @foreach ($nav as $heading => $items)
            @php($visible = array_filter($items, fn ($i) => $i[4]))
            @continue(empty($visible))
            <div class="op-nav-heading">{{ $heading }}</div>
            @foreach ($visible as $item)
                <a href="{{ route($item[1]) }}" class="op-nav-link {{ request()->routeIs($item[3]) ? 'active' : '' }}"
                   @if (request()->routeIs($item[3])) aria-current="page" @endif title="{{ $item[0] }}">
                    <i class="bi {{ $item[2] }}"></i><span>{{ $item[0] }}</span>
                    @if (! empty($item[5]))<span class="badge rounded-pill text-bg-danger">{{ $item[5] > 99 ? '99+' : $item[5] }}</span>@endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="op-sidebar-footer">
        <a href="{{ route('profile.edit') }}" class="op-sidebar-user">
            <x-avatar :name="$u->name" :src="$u->photo_url" size="36" />
            <span class="meta"><strong>{{ $u->name }}</strong><span>{{ $u->role?->name }}</span></span>
        </a>
    </div>
</aside>
