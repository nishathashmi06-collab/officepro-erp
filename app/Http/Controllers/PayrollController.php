<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Notifications\PayrollGenerated;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->query('month');

        $query = Payroll::visibleTo($user)
            ->with(['employee.department', 'employee.designation'])
            ->when($month, fn ($q) => $q->whereDate('period', Carbon::createFromFormat('Y-m', $month)->startOfMonth()))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('department'), fn ($q, $v) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $v)))
            ->when($request->query('q'), fn ($q, $v) => $q->whereHas('employee', fn ($e) => $e->search($v)));

        $totals = (clone $query)->reorder()->selectRaw('COUNT(*) as records, COALESCE(SUM(gross_salary),0) as gross, COALESCE(SUM(net_salary),0) as net')->first();

        return view('payroll.index', [
            'payrolls' => $query->latest('period')->orderBy('employee_id')->paginate(20)->withQueryString(),
            'totals' => $totals,
            'month' => $month,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'manage' => $user->hasPermission('payroll.manage'),
            'viewAll' => $user->hasPermission('payroll.view_all'),
        ]);
    }

    public function create(): View
    {
        return view('payroll.create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::active()->with('department')->orderBy('first_name')->get(),
            'defaultMonth' => today()->format('Y-m'),
        ]);
    }

    /**
     * Generate draft payroll for a month. Basic salary is taken from the
     * employee record (monthly) and unpaid leave days are deducted pro rata.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'allowance_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();

        $employees = Employee::whereIn('status', ['active', 'on_leave'])
            ->whereDate('joining_date', '<=', $period->copy()->endOfMonth())
            ->when($data['department_id'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when(! empty($data['employee_ids']), fn ($q) => $q->whereIn('id', $data['employee_ids']))
            ->get();

        $existing = Payroll::whereDate('period', $period)->pluck('employee_id')->all();
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($employees, $existing, $period, $data, $request, &$created, &$skipped) {
            foreach ($employees as $employee) {
                if (in_array($employee->id, $existing)) {
                    $skipped++;

                    continue;
                }

                $basic = (float) $employee->salary;
                $unpaidDays = (float) $employee->leaves()
                    ->where('status', 'approved')
                    ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
                    ->whereBetween('start_date', [$period->toDateString(), $period->copy()->endOfMonth()->toDateString()])
                    ->sum('days');
                $workingDays = max(1, \App\Models\Leave::countDays($period->toDateString(), $period->copy()->endOfMonth()->toDateString()));

                $allowances = round($basic * $data['allowance_percent'] / 100, 2);
                $deductions = round($basic / $workingDays * $unpaidDays, 2);
                $tax = round(($basic + $allowances) * $data['tax_percent'] / 100, 2);

                Payroll::create([
                    'employee_id' => $employee->id,
                    'period' => $period->toDateString(),
                    'basic_salary' => $basic,
                    'allowances' => $allowances,
                    'overtime' => 0,
                    'bonus' => 0,
                    'deductions' => $deductions,
                    'tax' => $tax,
                    'other_deductions' => 0,
                    'status' => 'draft',
                    'generated_by' => $request->user()->id,
                    'notes' => $unpaidDays > 0 ? "Includes {$unpaidDays} unpaid leave day(s)." : null,
                ]);
                $created++;
            }
        });

        activity('generated', 'payroll', "{$request->user()->name} generated payroll for {$period->format('F Y')} ({$created} records)");

        return redirect()->route('payroll.index', ['month' => $period->format('Y-m')])
            ->with($created ? 'success' : 'warning', "Generated {$created} payroll record(s) for {$period->format('F Y')}.".($skipped ? " {$skipped} already existed and were skipped." : ''));
    }

    public function show(Payroll $payroll): View
    {
        $this->authorize('view', $payroll);
        $payroll->load(['employee.department', 'employee.designation', 'generator', 'approver']);

        return view('payroll.show', compact('payroll'));
    }

    public function edit(Payroll $payroll): View
    {
        $this->authorize('update', $payroll);
        $payroll->load('employee');

        return view('payroll.edit', compact('payroll'));
    }

    public function update(PayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $data = $request->validated();
        $becameApproved = $data['status'] === 'approved' && $payroll->status !== 'approved';

        if ($becameApproved) {
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        $payroll->update($data);
        activity('updated', 'payroll', "Updated payroll of {$payroll->employee->full_name} for {$payroll->period_label}", $payroll);

        if ($becameApproved) {
            $payroll->employee->user?->notify(new PayrollGenerated($payroll, 'approved'));
        }

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll updated. Net salary: '.money($payroll->net_salary));
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        $this->authorize('delete', $payroll);
        $payroll->delete();
        activity('deleted', 'payroll', "Deleted payroll of {$payroll->employee->full_name} for {$payroll->period_label}", $payroll);

        return redirect()->route('payroll.index')->with('success', 'Payroll record deleted.');
    }

    public function approve(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('approve', $payroll);
        $this->approveRecord($payroll, $request->user()->id);

        return back()->with('success', 'Payroll approved.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $period = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();

        $records = Payroll::with('employee.user')->whereDate('period', $period)->whereIn('status', ['draft', 'pending'])->get();
        foreach ($records as $payroll) {
            $this->approveRecord($payroll, $request->user()->id, false);
        }

        activity('approved', 'payroll', "{$request->user()->name} approved {$records->count()} payroll record(s) for {$period->format('F Y')}");

        return back()->with('success', "Approved {$records->count()} payroll record(s).");
    }

    public function markPaid(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('markPaid', $payroll);
        $data = $request->validate(['payment_method' => ['required', 'in:bank_transfer,cash,cheque,other']]);

        $payroll->update(['status' => 'paid', 'paid_at' => now(), 'payment_method' => $data['payment_method']]);
        activity('paid', 'payroll', "Marked salary of {$payroll->employee->full_name} for {$payroll->period_label} as paid", $payroll);
        $payroll->employee->user?->notify(new PayrollGenerated($payroll, 'paid'));

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function slip(Request $request, Payroll $payroll): Response
    {
        $this->authorize('view', $payroll);
        $payroll->load(['employee.department', 'employee.designation']);

        $pdf = Pdf::loadView('payroll.slip', ['payroll' => $payroll])->setPaper('a4');
        $filename = sprintf('salary-slip-%s-%s.pdf', $payroll->employee->employee_code, $payroll->period->format('Y-m'));

        activity('downloaded', 'payroll', "{$request->user()->name} downloaded salary slip {$filename}", $payroll);

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    private function approveRecord(Payroll $payroll, int $userId, bool $log = true): void
    {
        $payroll->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        if ($log) {
            activity('approved', 'payroll', "Approved payroll of {$payroll->employee->full_name} for {$payroll->period_label}", $payroll);
        }
        $payroll->employee->user?->notify(new PayrollGenerated($payroll, 'approved'));
    }
}
