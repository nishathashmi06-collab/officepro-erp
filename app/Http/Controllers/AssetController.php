<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Employee;
use App\Notifications\AssetAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = Asset::with('employee')
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('asset_code', 'like', "%{$v}%")->orWhere('serial_number', 'like', "%{$v}%")))
            ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('employee'), fn ($q, $v) => $q->where('employee_id', $v))
            ->orderBy('asset_code')
            ->paginate(15)
            ->withQueryString();

        return view('assets.index', [
            'assets' => $assets,
            'counts' => Asset::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'totalValue' => (float) Asset::where('status', '!=', 'retired')->sum('purchase_price'),
            'employees' => Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('assets.manage');

        return view('assets.form', ['asset' => new Asset(['asset_code' => Asset::nextCode(), 'condition' => 'new', 'status' => 'available'])]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['asset_code'] = $data['asset_code'] ?: Asset::nextCode();
        $asset = Asset::create($data);
        activity('created', 'assets', "Added asset {$asset->name} ({$asset->asset_code})", $asset);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset added.');
    }

    public function show(Asset $asset): View
    {
        $asset->load(['employee.department', 'assignments.employee', 'assignments.assigner', 'maintenances.logger']);

        return view('assets.show', [
            'asset' => $asset,
            'employees' => Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']),
            'history' => ActivityLog::with('user')->where('module', 'assets')->where('record_id', $asset->id)->latest('created_at')->latest('id')->limit(30)->get(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        $this->authorize('assets.manage');

        return view('assets.form', compact('asset'));
    }

    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();
        $data['asset_code'] = $data['asset_code'] ?: $asset->asset_code;

        // Assignment status is controlled by assign/return actions only.
        if ($asset->status === 'assigned') {
            unset($data['status']);
        }

        $asset->update($data);
        activity('updated', 'assets', "Updated asset {$asset->name} ({$asset->asset_code})", $asset);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('assets.manage');

        if ($asset->status === 'assigned') {
            return back()->with('error', 'Return this asset before deleting it.');
        }

        $asset->delete();
        activity('deleted', 'assets', "Deleted asset {$asset->name} ({$asset->asset_code})", $asset);

        return redirect()->route('assets.index')->with('success', 'Asset deleted.');
    }

    public function assign(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('assets.manage');

        if ($asset->status !== 'available') {
            return back()->with('error', 'Only available assets can be assigned.');
        }

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'assigned_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['employee_id' => 'employee']);

        DB::transaction(function () use ($asset, $data, $request) {
            $asset->assignments()->create([
                'employee_id' => $data['employee_id'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => $data['assigned_at'],
                'condition_on_assign' => $asset->condition,
                'notes' => $data['notes'] ?? null,
            ]);
            $asset->update(['employee_id' => $data['employee_id'], 'status' => 'assigned']);
        });

        $asset->load('employee.user');
        activity('assigned', 'assets', "Assigned {$asset->name} ({$asset->asset_code}) to {$asset->employee->full_name}", $asset);
        $asset->employee->user?->notify(new AssetAssigned($asset));

        return back()->with('success', "Asset assigned to {$asset->employee->full_name}.");
    }

    public function return(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('assets.manage');

        if ($asset->status !== 'assigned') {
            return back()->with('error', 'This asset is not currently assigned.');
        }

        $data = $request->validate([
            'returned_at' => ['required', 'date', 'before_or_equal:today'],
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $employeeName = $asset->employee?->full_name ?? 'employee';

        DB::transaction(function () use ($asset, $data, $request) {
            $assignment = $asset->assignments()->whereNull('returned_at')->first();
            $assignment?->update([
                'returned_at' => $data['returned_at'],
                'returned_to' => $request->user()->id,
                'condition_on_return' => $data['condition'],
                'notes' => trim(($assignment->notes ? $assignment->notes."\n" : '').($data['notes'] ?? '')) ?: null,
            ]);
            $asset->update(['employee_id' => null, 'status' => 'available', 'condition' => $data['condition']]);
        });

        activity('returned', 'assets', "{$asset->name} ({$asset->asset_code}) returned by {$employeeName}", $asset);

        return back()->with('success', 'Asset returned and marked available.');
    }

    public function storeMaintenance(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('assets.manage');

        if ($asset->status === 'assigned') {
            return back()->with('error', 'Return the asset before sending it for maintenance.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'started_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $asset->maintenances()->create($data + ['cost' => $data['cost'] ?? 0, 'logged_by' => $request->user()->id]);
        $asset->update(['status' => 'maintenance']);
        activity('maintenance', 'assets', "Sent {$asset->name} ({$asset->asset_code}) for maintenance: {$data['title']}", $asset);

        return back()->with('success', 'Maintenance logged; asset marked as under maintenance.');
    }

    public function completeMaintenance(Request $request, Asset $asset, AssetMaintenance $maintenance): RedirectResponse
    {
        $this->authorize('assets.manage');
        abort_unless($maintenance->asset_id == $asset->id, 404);

        $data = $request->validate([
            'completed_at' => ['required', 'date', 'after_or_equal:'.$maintenance->started_at->toDateString()],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
        ]);

        $maintenance->update(['completed_at' => $data['completed_at'], 'cost' => $data['cost'] ?? $maintenance->cost]);

        if (! $asset->maintenances()->whereNull('completed_at')->exists()) {
            $asset->update(['status' => 'available', 'condition' => $data['condition']]);
        }

        activity('maintenance', 'assets', "Completed maintenance \"{$maintenance->title}\" for {$asset->asset_code}", $asset);

        return back()->with('success', 'Maintenance completed.');
    }
}
