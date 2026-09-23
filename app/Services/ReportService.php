<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the eight OfficePro reports from live data. Each report exposes
 * a query (for paginated screen output and chunked exports), column
 * definitions, a row mapper and summary figures.
 */
class ReportService
{
    public static function types(): array
    {
        return [
            'employees' => ['title' => 'Employee Report', 'icon' => 'bi-people', 'color' => 'primary', 'permission' => 'reports.view', 'description' => 'Headcount, departments, designations and employment details.', 'statuses' => Employee::STATUSES, 'date_label' => 'Joining date'],
            'attendance' => ['title' => 'Attendance Report', 'icon' => 'bi-fingerprint', 'color' => 'success', 'permission' => 'reports.view', 'description' => 'Daily check-ins, working hours, lateness and absence.', 'statuses' => Attendance::STATUSES, 'date_label' => 'Date'],
            'leaves' => ['title' => 'Leave Report', 'icon' => 'bi-calendar2-check', 'color' => 'warning', 'permission' => 'reports.view', 'description' => 'Leave requests by type, status and duration.', 'statuses' => Leave::STATUSES, 'date_label' => 'Leave dates'],
            'payroll' => ['title' => 'Payroll Report', 'icon' => 'bi-cash-stack', 'color' => 'info', 'permission' => 'payroll.view_all', 'description' => 'Earnings, deductions and net pay per salary month.', 'statuses' => Payroll::STATUSES, 'date_label' => 'Salary month'],
            'expenses' => ['title' => 'Expense Report', 'icon' => 'bi-receipt', 'color' => 'danger', 'permission' => 'expenses.view_all', 'description' => 'Office spending by category, method and approval status.', 'statuses' => Expense::STATUSES, 'date_label' => 'Expense date'],
            'tasks' => ['title' => 'Task Report', 'icon' => 'bi-kanban', 'color' => 'primary', 'permission' => 'tasks.view_all', 'description' => 'Task progress, priorities, deadlines and completion.', 'statuses' => Task::STATUSES, 'date_label' => 'Due date'],
            'assets' => ['title' => 'Asset Report', 'icon' => 'bi-laptop', 'color' => 'secondary', 'permission' => 'assets.view', 'description' => 'Asset register with assignment, condition and value.', 'statuses' => Asset::STATUSES, 'date_label' => 'Purchase date'],
            'documents' => ['title' => 'Document Report', 'icon' => 'bi-folder2-open', 'color' => 'success', 'permission' => 'documents.view_all', 'description' => 'Document inventory and expiry tracking.', 'statuses' => ['valid', 'expiring', 'expired'], 'date_label' => 'Upload date'],
        ];
    }

    public static function allowedTypes(User $user): array
    {
        return array_filter(self::types(), fn ($t) => $user->hasPermission('reports.view') && $user->hasPermission($t['permission']));
    }

    public function query(string $type, array $f): Builder
    {
        $from = $f['from'] ?? null;
        $to = $f['to'] ?? null;
        $dept = $f['department'] ?? null;
        $emp = $f['employee'] ?? null;
        $status = $f['status'] ?? null;

        $byEmployeeDept = fn (Builder $q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $dept));

        return match ($type) {
            'employees' => Employee::with(['department', 'designation'])
                ->when($from, fn ($q) => $q->whereDate('joining_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('joining_date', '<=', $to))
                ->when($dept, fn ($q) => $q->where('department_id', $dept))
                ->when($emp, fn ($q) => $q->where('id', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderBy('employee_code'),

            'attendance' => Attendance::with('employee.department')
                ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
                ->when($dept, $byEmployeeDept)
                ->when($emp, fn ($q) => $q->where('employee_id', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('date')->orderBy('employee_id'),

            'leaves' => Leave::with(['employee.department', 'leaveType', 'reviewer'])
                ->when($from, fn ($q) => $q->whereDate('end_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('start_date', '<=', $to))
                ->when($dept, $byEmployeeDept)
                ->when($emp, fn ($q) => $q->where('employee_id', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('start_date'),

            'payroll' => Payroll::with('employee.department')
                ->when($from, fn ($q) => $q->whereDate('period', '>=', substr($from, 0, 7).'-01'))
                ->when($to, fn ($q) => $q->whereDate('period', '<=', $to))
                ->when($dept, $byEmployeeDept)
                ->when($emp, fn ($q) => $q->where('employee_id', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('period')->orderBy('employee_id'),

            'expenses' => Expense::with(['category', 'creator'])
                ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
                ->when($emp, fn ($q) => $q->whereHas('creator.employee', fn ($e) => $e->where('id', $emp)))
                ->when($dept, fn ($q) => $q->whereHas('creator.employee', fn ($e) => $e->where('department_id', $dept)))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('date'),

            'tasks' => Task::with(['assignee', 'department'])
                ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
                ->when($dept, fn ($q) => $q->where('department_id', $dept))
                ->when($emp, fn ($q) => $q->where('assigned_to', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw('due_date IS NULL')->orderBy('due_date'),

            'assets' => Asset::with('employee')
                ->when($from, fn ($q) => $q->whereDate('purchase_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('purchase_date', '<=', $to))
                ->when($dept, $byEmployeeDept)
                ->when($emp, fn ($q) => $q->where('employee_id', $emp))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderBy('asset_code'),

            'documents' => Document::with(['employee', 'uploader'])
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->when($dept, $byEmployeeDept)
                ->when($emp, fn ($q) => $q->where('employee_id', $emp))
                ->when($status === 'expired', fn ($q) => $q->whereDate('expiry_date', '<', today()))
                ->when($status === 'expiring', fn ($q) => $q->whereBetween('expiry_date', [today()->toDateString(), today()->addDays((int) setting('document_expiry_days', 30))->toDateString()]))
                ->when($status === 'valid', fn ($q) => $q->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>', today()->addDays((int) setting('document_expiry_days', 30)))))
                ->latest(),
        };
    }

    /** @return array<string, string> */
    public function columns(string $type): array
    {
        return match ($type) {
            'employees' => ['code' => 'Employee ID', 'name' => 'Name', 'email' => 'Email', 'department' => 'Department', 'designation' => 'Designation', 'type' => 'Type', 'joining' => 'Joined', 'salary' => 'Salary', 'status' => 'Status'],
            'attendance' => ['date' => 'Date', 'employee' => 'Employee', 'department' => 'Department', 'check_in' => 'Check In', 'check_out' => 'Check Out', 'hours' => 'Hours', 'status' => 'Status'],
            'leaves' => ['employee' => 'Employee', 'department' => 'Department', 'type' => 'Leave Type', 'start' => 'From', 'end' => 'To', 'days' => 'Days', 'status' => 'Status', 'reviewer' => 'Reviewed By'],
            'payroll' => ['period' => 'Month', 'employee' => 'Employee', 'department' => 'Department', 'basic' => 'Basic', 'allowances' => 'Allowances', 'overtime' => 'Overtime', 'bonus' => 'Bonus', 'deductions' => 'Deductions', 'gross' => 'Gross', 'net' => 'Net', 'status' => 'Status'],
            'expenses' => ['date' => 'Date', 'title' => 'Title', 'category' => 'Category', 'amount' => 'Amount', 'method' => 'Payment Method', 'submitted_by' => 'Submitted By', 'status' => 'Status'],
            'tasks' => ['title' => 'Task', 'assignee' => 'Assignee', 'department' => 'Department', 'priority' => 'Priority', 'status' => 'Status', 'due' => 'Due Date', 'progress' => 'Progress'],
            'assets' => ['code' => 'Asset ID', 'name' => 'Name', 'category' => 'Category', 'serial' => 'Serial No.', 'assigned' => 'Assigned To', 'location' => 'Location', 'condition' => 'Condition', 'status' => 'Status', 'price' => 'Purchase Price'],
            'documents' => ['title' => 'Title', 'category' => 'Category', 'employee' => 'Employee', 'expiry' => 'Expiry Date', 'state' => 'Expiry Status', 'uploaded_by' => 'Uploaded By', 'uploaded' => 'Uploaded'],
        };
    }

    /** Numeric columns are right-aligned on screen/PDF. */
    public function numericColumns(string $type): array
    {
        return ['salary', 'hours', 'days', 'basic', 'allowances', 'overtime', 'bonus', 'deductions', 'gross', 'net', 'amount', 'price', 'progress'];
    }

    public function row(string $type, Model $m): array
    {
        return match ($type) {
            'employees' => ['code' => $m->employee_code, 'name' => $m->full_name, 'email' => $m->email, 'department' => $m->department?->name ?? '—', 'designation' => $m->designation?->name ?? '—', 'type' => label($m->employment_type), 'joining' => fmt_date($m->joining_date), 'salary' => money($m->salary), 'status' => label($m->status)],
            'attendance' => ['date' => fmt_date($m->date), 'employee' => $m->employee?->full_name ?? '—', 'department' => $m->employee?->department?->name ?? '—', 'check_in' => fmt_time($m->check_in), 'check_out' => fmt_time($m->check_out), 'hours' => number_format((float) $m->working_hours, 2), 'status' => label($m->status)],
            'leaves' => ['employee' => $m->employee?->full_name ?? '—', 'department' => $m->employee?->department?->name ?? '—', 'type' => $m->leaveType?->name, 'start' => fmt_date($m->start_date), 'end' => fmt_date($m->end_date), 'days' => (float) $m->days, 'status' => label($m->status), 'reviewer' => $m->reviewer?->name ?? '—'],
            'payroll' => ['period' => $m->period_label, 'employee' => $m->employee?->full_name ?? '—', 'department' => $m->employee?->department?->name ?? '—', 'basic' => money($m->basic_salary), 'allowances' => money($m->allowances), 'overtime' => money($m->overtime), 'bonus' => money($m->bonus), 'deductions' => money($m->total_deductions), 'gross' => money($m->gross_salary), 'net' => money($m->net_salary), 'status' => label($m->status)],
            'expenses' => ['date' => fmt_date($m->date), 'title' => $m->title, 'category' => $m->category?->name ?? '—', 'amount' => money($m->amount), 'method' => label($m->payment_method), 'submitted_by' => $m->creator?->name ?? '—', 'status' => label($m->status)],
            'tasks' => ['title' => $m->title, 'assignee' => $m->assignee?->full_name ?? '—', 'department' => $m->department?->name ?? '—', 'priority' => label($m->priority), 'status' => label($m->status), 'due' => fmt_date($m->due_date), 'progress' => $m->progress.'%'],
            'assets' => ['code' => $m->asset_code, 'name' => $m->name, 'category' => label($m->category), 'serial' => $m->serial_number ?? '—', 'assigned' => $m->employee?->full_name ?? '—', 'location' => $m->location ?? '—', 'condition' => label($m->condition), 'status' => label($m->status), 'price' => $m->purchase_price !== null ? money($m->purchase_price) : '—'],
            'documents' => ['title' => $m->title, 'category' => label($m->category), 'employee' => $m->employee?->full_name ?? 'Company-wide', 'expiry' => fmt_date($m->expiry_date), 'state' => label($m->expiryState() ?? 'no expiry'), 'uploaded_by' => $m->uploader?->name ?? '—', 'uploaded' => fmt_date($m->created_at)],
        };
    }

    /** @return array<string, string|int> label => value */
    public function summary(string $type, array $f): array
    {
        $q = fn () => $this->query($type, $f)->reorder();

        return match ($type) {
            'employees' => [
                'Employees' => $q()->count(),
                'Active' => $q()->where('status', 'active')->count(),
                'Monthly salary (all listed)' => money($q()->sum('salary')),
            ],
            'attendance' => [
                'Records' => $q()->count(),
                'Present' => $q()->where('status', 'present')->count(),
                'Late' => $q()->where('status', 'late')->count(),
                'Absent' => $q()->where('status', 'absent')->count(),
                'Total hours' => number_format((float) $q()->sum('working_hours'), 1),
            ],
            'leaves' => [
                'Requests' => $q()->count(),
                'Approved days' => (float) $q()->where('status', 'approved')->sum('days'),
                'Pending' => $q()->where('status', 'pending')->count(),
                'Rejected' => $q()->where('status', 'rejected')->count(),
            ],
            'payroll' => [
                'Records' => $q()->count(),
                'Total gross' => money($q()->sum('gross_salary')),
                'Total net' => money($q()->sum('net_salary')),
                'Paid' => $q()->where('status', 'paid')->count(),
            ],
            'expenses' => [
                'Expenses' => $q()->count(),
                'Approved total' => money($q()->where('status', 'approved')->sum('amount')),
                'Pending total' => money($q()->where('status', 'pending')->sum('amount')),
            ],
            'tasks' => [
                'Tasks' => $total = $q()->count(),
                'Completed' => $done = $q()->where('status', 'completed')->count(),
                'Completion rate' => $total ? round($done / $total * 100).'%' : '0%',
                'Overdue' => $q()->open()->whereDate('due_date', '<', today())->count(),
            ],
            'assets' => [
                'Assets' => $q()->count(),
                'Assigned' => $q()->where('status', 'assigned')->count(),
                'Under maintenance' => $q()->where('status', 'maintenance')->count(),
                'Total value' => money($q()->sum('purchase_price')),
            ],
            'documents' => [
                'Documents' => $q()->count(),
                'Expired' => $q()->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())->count(),
                'Expiring soon' => $q()->whereBetween('expiry_date', [today()->toDateString(), today()->addDays((int) setting('document_expiry_days', 30))->toDateString()])->count(),
            ],
        };
    }
}
