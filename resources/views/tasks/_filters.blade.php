<form method="GET" class="row g-2 align-items-end op-filters">
    <div class="col-lg-3 col-md-4"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Task title…"></div>
    @if ($employees->count() > 1)
        <div class="col-lg-2 col-md-4 col-6"><label class="form-label small">Assignee</label>
            <select name="assignee" class="form-select"><option value="">Anyone</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('assignee') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-4 col-6"><label class="form-label small">Department</label>
            <select name="department" class="form-select"><option value="">All</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    @endif
    <div class="col-lg-2 col-md-4 col-6"><label class="form-label small">Priority</label>
        <select name="priority" class="form-select"><option value="">All</option>@foreach (App\Models\Task::PRIORITIES as $p)<option value="{{ $p }}" @selected(request('priority') === $p)>{{ label($p) }}</option>@endforeach</select></div>
    @isset($showStatus)
        <div class="col-lg-2 col-md-4 col-6"><label class="form-label small">Status</label>
            <select name="status" class="form-select"><option value="">All</option>@foreach (App\Models\Task::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach</select></div>
    @endisset
    <div class="col-lg-1 col-md-4 col-6 d-flex gap-2">
        <button class="btn btn-primary flex-fill" title="Filter"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="col-12 d-flex flex-wrap gap-3 small">
        @if (auth()->user()->employee)
            <label class="form-check"><input type="checkbox" class="form-check-input" name="mine" value="1" @checked(request('mine')) onchange="this.form.submit()"> Assigned to me</label>
        @endif
        <label class="form-check"><input type="checkbox" class="form-check-input" name="overdue" value="1" @checked(request('overdue')) onchange="this.form.submit()"> Overdue only</label>
        @if (request()->except('page'))<a href="{{ url()->current() }}" class="ms-auto">Reset filters</a>@endif
    </div>
</form>
