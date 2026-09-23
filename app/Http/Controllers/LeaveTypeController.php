<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        return view('leave-types.index', ['types' => LeaveType::withCount('leaves')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('leave-types.form', ['type' => new LeaveType(['status' => 'active', 'is_paid' => true, 'color' => 'primary'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = LeaveType::create($this->validated($request));
        activity('created', 'leaves', "Created leave type {$type->name}", $type);

        return redirect()->route('leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('leave-types.form', ['type' => $leaveType]);
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($this->validated($request, $leaveType));
        activity('updated', 'leaves', "Updated leave type {$leaveType->name}", $leaveType);

        return redirect()->route('leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaves()->withTrashed()->exists()) {
            return back()->with('error', 'This leave type has leave records. Set it to inactive instead.');
        }

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    private function validated(Request $request, ?LeaveType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('leave_types', 'name')->ignore($type?->id)],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('leave_types', 'code')->ignore($type?->id)],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:365'],
            'color' => ['required', Rule::in(['primary', 'success', 'info', 'warning', 'danger', 'secondary'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['is_paid'] = $request->boolean('is_paid');
        $data['requires_attachment'] = $request->boolean('requires_attachment');

        return $data;
    }
}
