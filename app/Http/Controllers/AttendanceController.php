<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        [$from, $to, $period] = $this->resolvePeriod($request);

        $query = Attendance::visibleTo($user)
            ->with('employee.department')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->query('employee'), fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->query('department'), fn ($q, $v) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $v)))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v));

        $summary = (clone $query)->reorder()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalHours = (float) (clone $query)->sum('working_hours');

        $canFilterPeople = $user->hasPermission('attendance.view_all') || $user->hasPermission('attendance.view_team');
        $employee = $user->employee;

        return view('attendance.index', [
            'records' => $query->orderByDesc('date')->orderBy('employee_id')->paginate(20)->withQueryString(),
            'summary' => $summary,
            'totalHours' => $totalHours,
            'from' => $from,
            'to' => $to,
            'period' => $period,
            'canFilterPeople' => $canFilterPeople,
            'employees' => $canFilterPeople ? Employee::visibleTo($user)->orderBy('first_name')->get(['id', 'first_name', 'last_name']) : collect(),
            'departments' => $user->hasPermission('attendance.view_all') ? Department::orderBy('name')->get(['id', 'name']) : collect(),
            'employee' => $employee,
            'today' => $employee?->attendances()->whereDate('date', today())->first(),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return back()->with('error', 'Your account is not linked to an employee profile.');
        }
        if (! in_array($employee->status, ['active', 'on_leave'], true)) {
            return back()->with('error', 'Only active employees can check in.');
        }

        $now = now();
        if ($employee->attendances()->whereDate('date', $now->toDateString())->exists()) {
            return back()->with('error', 'You have already checked in today.');
        }

        $onLeave = Leave::where('employee_id', $employee->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $now)->whereDate('end_date', '>=', $now)->exists();
        if ($onLeave) {
            return back()->with('error', 'You are on approved leave today.');
        }

        try {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => $now->toDateString(),
                'check_in' => $now->format('H:i:s'),
                'status' => $this->isLate($now) ? 'late' : 'present',
                'check_in_ip' => $request->ip(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Double submit / race condition: the unique index keeps data consistent.
            return back()->with('error', 'You have already checked in today.');
        }

        activity('check_in', 'attendance', "{$employee->full_name} checked in at ".$now->format('h:i A'), $attendance);

        return back()->with('success', 'Checked in at '.$now->format('h:i A').($attendance->status === 'late' ? ' (late).' : '.'));
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        $attendance = $employee?->attendances()->whereDate('date', today())->first();

        if (! $attendance || ! $attendance->check_in) {
            return back()->with('error', 'You have not checked in today.');
        }
        if ($attendance->check_out) {
            return back()->with('error', 'You have already checked out today.');
        }

        $now = now();
        $hours = Attendance::calculateHours($attendance->check_in, $now->format('H:i:s'));

        $attendance->update([
            'check_out' => $now->format('H:i:s'),
            'working_hours' => $hours,
            'status' => $this->statusAfterCheckout($attendance->status, $hours),
        ]);

        activity('check_out', 'attendance', "{$employee->full_name} checked out at ".$now->format('h:i A')." ({$hours}h)", $attendance);

        return back()->with('success', "Checked out. You worked {$hours} hours today.");
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        return view('attendance.form', [
            'attendance' => new Attendance(['date' => today(), 'status' => 'present', 'employee_id' => $request->query('employee')]),
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('first_name')->get(),
        ]);
    }

    public function store(AttendanceRequest $request): RedirectResponse
    {
        $attendance = Attendance::create($this->payload($request) + ['updated_by' => $request->user()->id]);
        activity('created', 'attendance', "Recorded attendance for {$attendance->employee->full_name} on ".$attendance->date->format('M d, Y'), $attendance);

        return redirect()->route('attendance.index', ['period' => 'custom', 'from' => $attendance->date->toDateString(), 'to' => $attendance->date->toDateString()])
            ->with('success', 'Attendance recorded.');
    }

    public function edit(Attendance $attendance): View
    {
        $this->authorize('update', $attendance);

        return view('attendance.form', [
            'attendance' => $attendance,
            'employees' => Employee::orderBy('first_name')->get(),
        ]);
    }

    public function update(AttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update($this->payload($request) + ['updated_by' => $request->user()->id]);
        activity('updated', 'attendance', "Edited attendance for {$attendance->employee->full_name} on ".$attendance->date->format('M d, Y'), $attendance);

        return redirect()->route('attendance.index')->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);
        $attendance->delete();
        activity('deleted', 'attendance', "Deleted attendance for {$attendance->employee->full_name} on ".$attendance->date->format('M d, Y'), $attendance);

        return back()->with('success', 'Attendance record deleted.');
    }

    private function payload(AttendanceRequest $request): array
    {
        $data = $request->validated();
        $in = $data['check_in'] ?? null;
        $out = $data['check_out'] ?? null;

        if (in_array($data['status'], ['absent', 'leave'], true)) {
            $in = $out = null;
        }

        return [
            'employee_id' => $data['employee_id'],
            'date' => $data['date'],
            'status' => $data['status'],
            'check_in' => $in,
            'check_out' => $out,
            'working_hours' => Attendance::calculateHours($in, $out),
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function isLate(Carbon $time): bool
    {
        $start = Carbon::parse($time->toDateString().' '.setting('work_start_time', '09:00'))
            ->addMinutes((int) setting('late_grace_minutes', 15));

        return $time->greaterThan($start);
    }

    private function statusAfterCheckout(string $current, float $hours): string
    {
        $fullDay = (float) setting('default_working_hours', 8);

        return $hours < $fullDay / 2 ? 'half_day' : $current;
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', 'month');

        return match ($period) {
            'today' => [today(), today(), 'today'],
            'week' => [today()->startOfWeek(), today()->endOfWeek(), 'week'],
            'custom' => $this->customRange($request),
            default => [today()->startOfMonth(), today()->endOfMonth(), 'month'],
        };
    }

    private function customRange(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->query('from') ? Carbon::parse($request->query('from')) : today()->startOfMonth();
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : today();

        return [$from, $to, 'custom'];
    }
}
