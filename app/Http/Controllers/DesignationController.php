<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function index(Request $request): View
    {
        return view('designations.index', [
            'designations' => Designation::withCount('employees')
                ->when($request->query('q'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('designations.form', ['designation' => new Designation(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $designation = Designation::create($this->validated($request));
        activity('created', 'designations', "Created designation {$designation->name}", $designation);

        return redirect()->route('designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation): View
    {
        return view('designations.form', compact('designation'));
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $designation->update($this->validated($request, $designation));
        activity('updated', 'designations', "Updated designation {$designation->name}", $designation);

        return redirect()->route('designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        if ($designation->employees()->exists()) {
            return back()->with('error', 'This designation is assigned to employees and cannot be deleted.');
        }

        $designation->delete();
        activity('deleted', 'designations', "Deleted designation {$designation->name}", $designation);

        return back()->with('success', 'Designation deleted.');
    }

    private function validated(Request $request, ?Designation $designation = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->ignore($designation?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
