<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Task;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LeaveBalanceService $balances): View
    {
        $user = $request->user();
        $employee = $user->employee;
        $today = today();
        $orgView = $user->hasPermission('employees.view_all') || $user->hasPermission('employees.view_team');

        // ---- Headline stats (always scoped to what the user may see) ----
        $totalEmployees = Employee::visibleTo($user)->count();
        $activeEmployees = Employee::visibleTo($user)->active()->count();

        $todayByStatus = Attendance::visibleTo($user)
            ->whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $presentToday = (int) ($todayByStatus['present'] ?? 0) + (int) ($todayByStatus['late'] ?? 0) + (int) ($todayByStatus['half_day'] ?? 0);
        $lateToday = (int) ($todayByStatus['late'] ?? 0);

        $onLeaveToday = Leave::visibleTo($user)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->distinct()
            ->count('employee_id');

        // Absent = active employees with no check-in who are not on leave (weekdays only).
        $absentToday = $today->isWeekend()
            ? (int) ($todayByStatus['absent'] ?? 0)
            : max(0, $activeEmployees - $presentToday - $onLeaveToday);

        $stats = [
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'late_today' => $lateToday,
            'on_leave_today' => $onLeaveToday,
            'pending_leaves' => Leave::visibleTo($user)->where('status', 'pending')->count(),
            'pending_tasks' => Task::visibleTo($user)->open()->count(),
            'monthly_payroll' => $user->hasPermission('payroll.view_all')
                ? (float) Payroll::whereDate('period', $today->copy()->startOfMonth())->sum('net_salary')
                : null,
            'monthly_expenses' => $user->hasPermission('expenses.view_all')
                ? (float) Expense::where('status', 'approved')->whereBetween('date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->sum('amount')
                : null,
        ];

        // ---- Charts ----
        $charts = [
            'attendance' => $this->attendanceChart($user),
            'tasks' => $this->taskChart($user),
            'leaves' => $this->leaveChart($user),
            'departments' => $orgView ? $this->departmentChart($user) : null,
            'expenses' => $user->hasPermission('expenses.view_all') ? $this->expenseChart() : null,
            'payroll' => $user->hasPermission('payroll.view_all') ? $this->payrollChart() : null,
        ];

        // ---- Lists ----
        $recentActivities = ActivityLog::with('user')
            ->when(! $user->hasPermission('activity_logs.view'), fn ($q) => $q->where('user_id', $user->id))
            ->latest('created_at')->latest('id')
            ->limit(8)->get();

        $pendingLeaves = Leave::visibleTo($user)->with(['employee', 'leaveType'])
            ->where('status', 'pending')->orderBy('start_date')->limit(5)->get();

        $upcomingTasks = Task::visibleTo($user)->with('assignee')
            ->open()->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(14))
            ->orderBy('due_date')->limit(6)->get();

        $expiringDocuments = Document::visibleTo($user)->with('employee')
            ->expiringWithin((int) setting('document_expiry_days', 30))
            ->orderBy('expiry_date')->limit(5)->get();

        $myMonth = $employee
            ? $employee->attendances()
                ->whereBetween('date', [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()])
                ->selectRaw("SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 ELSE 0 END) as days, COALESCE(SUM(working_hours),0) as hours, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late")
                ->first()
            : null;

        return view('dashboard.index', [
            'myMonth' => $myMonth,
            'stats' => $stats,
            'charts' => $charts,
            'orgView' => $orgView,
            'employee' => $employee,
            'todayAttendance' => $employee?->attendances()->whereDate('date', $today)->first(),
            'leaveBalances' => $employee ? $balances->forEmployee($employee) : collect(),
            'recentActivities' => $recentActivities,
            'birthdays' => $this->upcomingBirthdays(),
            'pendingLeaves' => $pendingLeaves,
            'upcomingTasks' => $upcomingTasks,
            'expiringDocuments' => $expiringDocuments,
            'greeting' => $this->greeting(),
        ]);
    }

    private function greeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 12 => 'Good Morning',
            $hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };
    }

    /** Last 7 weekdays: present / late / absent / leave counts. */
    private function attendanceChart($user): array
    {
        $days = collect();
        $date = today();
        while ($days->count() < 7) {
            if (! $date->isWeekend()) {
                $days->prepend($date->copy());
            }
            $date->subDay();
        }

        $rows = Attendance::visibleTo($user)
            ->whereBetween('date', [$days->first()->toDateString(), $days->last()->toDateString()])
            ->selectRaw('date, status, COUNT(*) as total')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $series = ['present' => [], 'late' => [], 'half_day' => [], 'absent' => [], 'leave' => []];
        foreach ($days as $day) {
            $forDay = ($rows[$day->toDateString()] ?? collect())->pluck('total', 'status');
            foreach ($series as $status => $_) {
                $series[$status][] = (int) ($forDay[$status] ?? 0);
            }
        }

        return [
            'labels' => $days->map(fn ($d) => $d->format('D d'))->all(),
            'series' => $series,
        ];
    }

    private function departmentChart($user): array
    {
        $visibleIds = Employee::visibleTo($user)->active()->pluck('id');

        $departments = Department::withCount(['employees' => fn ($q) => $q->whereIn('employees.id', $visibleIds)])
            ->orderByDesc('employees_count')
            ->get()
            ->filter(fn ($d) => $d->employees_count > 0);

        return [
            'labels' => $departments->pluck('name')->values()->all(),
            'data' => $departments->pluck('employees_count')->values()->all(),
        ];
    }

    private function lastMonths(int $count = 6): array
    {
        return collect(range($count - 1, 0))->map(fn ($i) => today()->startOfMonth()->subMonths($i))->all();
    }

    private function expenseChart(): array
    {
        $labels = $data = [];
        foreach ($this->lastMonths() as $month) {
            $labels[] = $month->format('M Y');
            $data[] = round((float) Expense::where('status', 'approved')
                ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                ->sum('amount'), 2);
        }

        return compact('labels', 'data');
    }

    private function payrollChart(): array
    {
        $labels = $gross = $net = [];
        foreach ($this->lastMonths() as $month) {
            $labels[] = $month->format('M Y');
            $row = Payroll::whereDate('period', $month->toDateString())
                ->selectRaw('COALESCE(SUM(gross_salary),0) as gross, COALESCE(SUM(net_salary),0) as net')
                ->first();
            $gross[] = round((float) $row->gross, 2);
            $net[] = round((float) $row->net, 2);
        }

        return compact('labels', 'gross', 'net');
    }

    private function taskChart($user): array
    {
        $counts = Task::visibleTo($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_map('label', Task::STATUSES),
            'data' => array_map(fn ($s) => (int) ($counts[$s] ?? 0), Task::STATUSES),
        ];
    }

    private function leaveChart($user): array
    {
        $year = today()->year;
        $types = LeaveType::orderBy('name')->get();

        $rows = Leave::visibleTo($user)
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, status, SUM(days) as total')
            ->groupBy('leave_type_id', 'status')
            ->get();

        $series = [];
        foreach (['approved', 'pending', 'rejected'] as $status) {
            $series[$status] = $types->map(fn ($t) => (float) ($rows->first(fn ($r) => $r->leave_type_id == $t->id && $r->status === $status)?->total ?? 0))->all();
        }

        return ['labels' => $types->pluck('name')->all(), 'series' => $series];
    }

    private function upcomingBirthdays(): \Illuminate\Support\Collection
    {
        $today = today();

        return Employee::active()
            ->whereNotNull('date_of_birth')
            ->with('department')
            ->get(['id', 'first_name', 'last_name', 'date_of_birth', 'department_id', 'profile_photo'])
            ->map(function (Employee $e) use ($today) {
                $next = $e->date_of_birth->copy()->year($today->year);
                if ($next->lt($today)) {
                    $next->addYear();
                }
                $e->next_birthday = $next;
                $e->days_until_birthday = (int) $today->diffInDays($next);

                return $e;
            })
            ->filter(fn ($e) => $e->days_until_birthday <= 30)
            ->sortBy('days_until_birthday')
            ->take(5)
            ->values();
    }
}
