<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequest;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    public function index(Request $request, LeaveBalanceService $balances): View
    {
        $user = $request->user();
        $scope = $request->query('scope', 'all');

        $leaves = Leave::visibleTo($user)
            ->with(['employee.department', 'leaveType', 'reviewer'])
            ->when($scope === 'mine', fn ($q) => $q->where('employee_id', $user->employee?->id ?? 0))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('type'), fn ($q, $v) => $q->where('leave_type_id', $v))
            ->when($request->query('employee'), fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->query('from'), fn ($q, $v) => $q->whereDate('end_date', '>=', $v))
            ->when($request->query('to'), fn ($q, $v) => $q->whereDate('start_date', '<=', $v))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        $canSeeOthers = $user->hasPermission('leaves.view_all') || $user->hasPermission('leaves.view_team');

        return view('leaves.index', [
            'leaves' => $leaves,
            'types' => LeaveType::orderBy('name')->get(),
            'balances' => $user->employee ? $balances->forEmployee($user->employee) : collect(),
            'canSeeOthers' => $canSeeOthers,
            'employees' => $canSeeOthers ? Employee::visibleTo($user)->orderBy('first_name')->get(['id', 'first_name', 'last_name']) : collect(),
            'counts' => Leave::visibleTo($user)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request, LeaveBalanceService $balances): View
    {
        $user = $request->user();
        abort_unless($user->employee || $user->hasPermission('leaves.view_all'), 403, 'Your account is not linked to an employee profile.');

        return view('leaves.create', [
            'types' => LeaveType::where('status', 'active')->orderBy('name')->get(),
            'balances' => $user->employee ? $balances->forEmployee($user->employee) : collect(),
            'employees' => $user->hasPermission('leaves.view_all') ? Employee::active()->orderBy('first_name')->get() : collect(),
        ]);
    }

    public function store(LeaveRequest $request): RedirectResponse
    {
        $employee = $request->targetEmployee();
        $data = $request->validated();

        $leave = new Leave([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => Leave::countDays($data['start_date'], $data['end_date']),
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        if ($request->hasFile('attachment')) {
            // Private disk: served only through the authorised download route.
            $leave->attachment = $request->file('attachment')->store('leave-attachments', 'local');
        }

        $leave->save();
        $leave->load(['employee', 'leaveType']);

        activity('requested', 'leaves', "{$employee->full_name} requested {$leave->leaveType->name} ({$leave->days} days)", $leave);

        Notification::send($this->approversFor($leave), new LeaveRequested($leave));

        return redirect()->route('leaves.show', $leave)->with('success', 'Leave request submitted for approval.');
    }

    public function show(Leave $leave, LeaveBalanceService $balances): View
    {
        $this->authorize('view', $leave);
        $leave->load(['employee.department', 'employee.designation', 'leaveType', 'reviewer']);

        return view('leaves.show', [
            'leave' => $leave,
            'balances' => $balances->forEmployee($leave->employee, $leave->start_date->year),
            'overlapping' => Leave::where('id', '!=', $leave->id)
                ->where('status', 'approved')
                ->whereHas('employee', fn ($q) => $q->where('department_id', $leave->employee->department_id))
                ->whereDate('start_date', '<=', $leave->end_date)
                ->whereDate('end_date', '>=', $leave->start_date)
                ->with('employee')->get(),
        ]);
    }

    public function approve(Request $request, Leave $leave): RedirectResponse
    {
        return $this->review($request, $leave, 'approved');
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        return $this->review($request, $leave, 'rejected');
    }

    public function cancel(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorize('cancel', $leave);
        $leave->update(['status' => 'cancelled']);
        activity('cancelled', 'leaves', "{$leave->employee->full_name} cancelled a {$leave->leaveType->name} request", $leave);

        return back()->with('success', 'Leave request cancelled.');
    }

    public function attachment(Leave $leave): StreamedResponse
    {
        $this->authorize('view', $leave);
        abort_unless($leave->attachment && Storage::disk('local')->exists($leave->attachment), 404);

        return Storage::disk('local')->download($leave->attachment, 'leave-'.$leave->id.'-attachment.'.pathinfo($leave->attachment, PATHINFO_EXTENSION));
    }

    private function review(Request $request, Leave $leave, string $status): RedirectResponse
    {
        $this->authorize('review', $leave);

        $data = $request->validate([
            'review_note' => [$status === 'rejected' ? 'required' : 'nullable', 'string', 'max:500'],
        ], ['review_note.required' => 'Please give a reason for rejecting this request.']);

        $leave->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        activity($status, 'leaves', "{$request->user()->name} {$status} {$leave->employee->full_name}'s {$leave->leaveType->name} request", $leave);

        $leave->employee->user?->notify(new LeaveReviewed($leave));

        return back()->with('success', "Leave request {$status}.");
    }

    /** HR / admins with global leave approval + the employee's department manager. */
    private function approversFor(Leave $leave)
    {
        $managerUser = $leave->employee->department?->manager?->user;

        return User::active()->with('role.permissions', 'employee')->get()
            ->filter(function (User $u) use ($leave, $managerUser) {
                if ($u->employee?->id == $leave->employee_id) {
                    return false;
                }

                return ($u->hasPermission('leaves.approve') && $u->hasPermission('leaves.view_all'))
                    || ($managerUser && $u->is($managerUser));
            });
    }
}
