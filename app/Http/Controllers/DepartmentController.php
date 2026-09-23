<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::with('manager')
            ->withCount(['employees', 'employees as active_employees_count' => fn ($q) => $q->where('status', 'active')])
            ->when($request->query('q'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.form', [
            'department' => new Department(['status' => 'active']),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());
        activity('created', 'departments', "Created department {$department->name}", $department);

        return redirect()->route('departments.show', $department)->with('success', 'Department created.');
    }

    public function show(Department $department): View
    {
        $department->load('manager.designation');

        return view('departments.show', [
            'department' => $department,
            'employees' => $department->employees()->with('designation')->orderBy('first_name')->paginate(15),
            'stats' => [
                'active' => $department->employees()->where('status', 'active')->count(),
                'open_tasks' => $department->tasks()->open()->count(),
                'monthly_salary' => (float) $department->employees()->where('status', 'active')->sum('salary'),
            ],
        ]);
    }

    public function edit(Department $department): View
    {
        return view('departments.form', [
            'department' => $department,
            'employees' => Employee::active()->orWhere('id', $department->manager_id)->orderBy('first_name')->get(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());
        activity('updated', 'departments', "Updated department {$department->name}", $department);

        return redirect()->route('departments.show', $department)->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->with('error', 'Move or remove the employees in this department before deleting it.');
        }

        $department->delete();
        activity('deleted', 'departments', "Deleted department {$department->name}", $department);

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
