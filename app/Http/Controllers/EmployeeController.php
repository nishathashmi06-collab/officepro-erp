<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    private const SORTABLE = ['employee_code', 'first_name', 'joining_date', 'salary', 'status', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Plain employees only have their own profile.
        if (! $user->can('viewAny', Employee::class)) {
            abort_unless($user->employee, 403);

            return redirect()->route('employees.show', $user->employee);
        }

        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'first_name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $employees = Employee::visibleTo($user)
            ->with(['department', 'designation'])
            ->search($request->query('q'))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->query('designation'), fn ($q, $v) => $q->where('designation_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('type'), fn ($q, $v) => $q->where('employment_type', $v))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'name']),
            'sort' => $sort,
            'direction' => $direction,
            'canSeeSalary' => $user->hasPermission('payroll.view_all') || $user->hasPermission('employees.edit'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.form', $this->formData(new Employee([
            'employee_code' => Employee::nextCode(),
            'joining_date' => today(),
            'employment_type' => 'full_time',
            'status' => 'active',
        ])));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $data = $this->payload($request);
            $data['employee_code'] = $data['employee_code'] ?: Employee::nextCode();

            if ($request->boolean('create_account')) {
                $data['user_id'] = $this->createAccount($request)->id;
            }

            return Employee::create($data);
        });

        activity('created', 'employees', "Created employee {$employee->full_name} ({$employee->employee_code})", $employee);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee created successfully.');
    }

    public function show(Request $request, Employee $employee, LeaveBalanceService $balances): View
    {
        $this->authorize('view', $employee);
        $user = $request->user();
        $canSensitive = $user->can('viewSensitive', $employee);

        $employee->load(['department.manager', 'designation', 'user.role', 'assets']);

        $month = today()->startOfMonth();
        $attendanceSummary = $employee->attendances()
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->selectRaw('status, COUNT(*) as total, SUM(working_hours) as hours')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $activity = ActivityLog::with('user')
            ->where(function ($q) use ($employee) {
                $q->where(fn ($q) => $q->where('module', 'employees')->where('record_id', $employee->id));
                if ($employee->user_id) {
                    $q->orWhere('user_id', $employee->user_id);
                }
            })
            ->latest('created_at')->latest('id')->limit(20)->get();

        return view('employees.show', [
            'employee' => $employee,
            'canSensitive' => $canSensitive,
            'attendances' => $employee->attendances()->latest('date')->limit(31)->get(),
            'attendanceSummary' => $attendanceSummary,
            'leaves' => $employee->leaves()->with('leaveType')->latest('start_date')->limit(20)->get(),
            'leaveBalances' => $balances->forEmployee($employee),
            'tasks' => $employee->tasks()->with('creator')->latest()->limit(20)->get(),
            'payrolls' => $canSensitive
                ? $employee->payrolls()->when(! $user->hasPermission('payroll.view_all'), fn ($q) => $q->whereIn('status', ['approved', 'paid']))->latest('period')->limit(12)->get()
                : collect(),
            'documents' => $canSensitive
                ? \App\Models\Document::visibleTo($user)->where('employee_id', $employee->id)->latest()->get()
                : collect(),
            'activity' => $activity,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.form', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee) {
            $data = $this->payload($request, $employee);
            $data['employee_code'] = $data['employee_code'] ?: $employee->employee_code;

            if ($request->boolean('create_account') && ! $employee->user_id) {
                $data['user_id'] = $this->createAccount($request)->id;
            }

            $employee->update($data);
        });

        activity('updated', 'employees', "Updated employee {$employee->full_name}", $employee);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    /** Soft-deactivate: keeps history intact and disables the login. */
    public function deactivate(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->update(['status' => 'inactive']);
        if ($employee->user && ! $employee->user->is_primary) {
            $employee->user->update(['status' => 'disabled']);
        }

        activity('deactivated', 'employees', "Deactivated employee {$employee->full_name}", $employee);

        return back()->with('success', 'Employee deactivated and login disabled.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        if ($employee->user?->is_primary) {
            return back()->with('error', 'The primary super admin cannot be removed.');
        }

        DB::transaction(function () use ($employee) {
            Department::where('manager_id', $employee->id)->update(['manager_id' => null]);
            $employee->assets()->update(['employee_id' => null, 'status' => 'available']);
            $employee->user?->update(['status' => 'disabled']);
            $employee->delete(); // soft delete keeps payroll/attendance history
        });

        activity('deleted', 'employees', "Deleted employee {$employee->full_name} ({$employee->employee_code})", $employee);

        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    private function formData(Employee $employee): array
    {
        $user = auth()->user();

        return [
            'employee' => $employee,
            'departments' => Department::where('status', 'active')->orWhere('id', $employee->department_id)->orderBy('name')->get(),
            'designations' => Designation::where('status', 'active')->orWhere('id', $employee->designation_id)->orderBy('name')->get(),
            'roles' => Role::orderBy('id')
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereNotIn('slug', ['super_admin', 'admin']))
                ->get(),
            'availableUsers' => User::whereDoesntHave('employee')
                ->orWhere('id', $employee->user_id)
                ->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    private function payload(EmployeeRequest $request, ?Employee $employee = null): array
    {
        $data = $request->safe()->except(['profile_photo', 'remove_photo', 'create_account', 'account_role_id', 'account_password']);

        if ($request->hasFile('profile_photo')) {
            if ($employee?->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('photos', 'public');
        } elseif ($request->boolean('remove_photo') && $employee?->profile_photo) {
            Storage::disk('public')->delete($employee->profile_photo);
            $data['profile_photo'] = null;
        }

        return $data;
    }

    private function createAccount(EmployeeRequest $request): User
    {
        abort_unless($request->user()->hasPermission('users.manage') || $request->user()->hasPermission('employees.create'), 403);

        $request->validate(['email' => ['unique:users,email']], ['email.unique' => 'A login account with this e-mail already exists.']);

        $account = User::create([
            'name' => $request->input('first_name').' '.$request->input('last_name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => $request->input('account_password'),
            'role_id' => $request->input('account_role_id'),
            'status' => 'active',
        ]);

        activity('created', 'users', "Created login account for {$account->name}", $account);

        return $account;
    }
}
