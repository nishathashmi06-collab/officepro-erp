<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $logs = ActivityLog::with('user')
            ->when($request->query('module'), fn ($q, $v) => $q->where('module', $v))
            ->when($request->query('user'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->when($request->query('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'users' => User::withTrashed()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
