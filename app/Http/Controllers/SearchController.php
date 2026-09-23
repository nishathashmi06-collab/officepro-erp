<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $term = trim((string) $request->query('q'));
        $user = $request->user();
        $groups = [];

        if (mb_strlen($term) >= 2) {
            $like = '%'.$term.'%';

            if ($user->can('viewAny', Employee::class) || $user->employee) {
                $groups['Employees'] = Employee::visibleTo($user)->with('department', 'designation')->search($term)->limit(8)->get()
                    ->map(fn ($e) => ['title' => $e->full_name, 'subtitle' => $e->employee_code.' · '.($e->designation?->name ?? '—').' · '.($e->department?->name ?? '—'), 'url' => route('employees.show', $e), 'icon' => 'bi-person']);
            }

            $groups['Tasks'] = Task::visibleTo($user)->with('assignee')->where('title', 'like', $like)->latest()->limit(8)->get()
                ->map(fn ($t) => ['title' => $t->title, 'subtitle' => label($t->status).' · '.($t->assignee?->full_name ?? 'Unassigned'), 'url' => route('tasks.show', $t), 'icon' => 'bi-kanban']);

            if ($user->can('departments.manage')) {
                $groups['Departments'] = Department::where('name', 'like', $like)->orWhere('code', 'like', $like)->limit(5)->get()
                    ->map(fn ($d) => ['title' => $d->name, 'subtitle' => $d->code ?? 'Department', 'url' => route('departments.show', $d), 'icon' => 'bi-diagram-3']);
            }

            $groups['Documents'] = Document::visibleTo($user)->with('employee')
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('original_name', 'like', $like))->limit(8)->get()
                ->map(fn ($d) => ['title' => $d->title, 'subtitle' => label($d->category).' · '.($d->employee?->full_name ?? 'Company-wide'), 'url' => route('documents.download', [$d, 'inline' => 1]), 'icon' => 'bi-file-earmark-text']);

            if ($user->can('assets.view')) {
                $groups['Assets'] = Asset::with('employee')
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('asset_code', 'like', $like)->orWhere('serial_number', 'like', $like))
                    ->limit(8)->get()
                    ->map(fn ($a) => ['title' => $a->name.' ('.$a->asset_code.')', 'subtitle' => label($a->status).($a->employee ? ' · '.$a->employee->full_name : ''), 'url' => route('assets.show', $a), 'icon' => 'bi-laptop']);
            }

            $groups = array_filter($groups, fn ($items) => $items->isNotEmpty());
        }

        if ($request->expectsJson()) {
            return response()->json(['groups' => $groups]);
        }

        return view('search.index', ['term' => $term, 'groups' => $groups, 'total' => collect($groups)->sum(fn ($g) => $g->count())]);
    }
}
