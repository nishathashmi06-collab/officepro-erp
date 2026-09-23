@extends('layouts.app')
@section('title', $meta['title'])
@section('content')
<x-page-header :title="$meta['title']" :subtitle="$meta['description']" :breadcrumbs="['Reports' => route('reports.index'), $meta['title'] => null]">
    <x-slot:actions>
        <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <a href="{{ route('reports.export', [$type, 'csv'] + request()->query()) }}" class="btn btn-light"><i class="bi bi-filetype-csv"></i> CSV / Excel</a>
        <a href="{{ route('reports.export', [$type, 'pdf'] + request()->query()) }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
    </x-slot:actions>
</x-page-header>

<div class="op-print-only mb-3">
    <h2>{{ setting('company_name') }} — {{ $meta['title'] }}</h2>
    <div>Generated {{ now()->format('M d, Y h:i A') }}</div>
</div>

<x-card class="mb-4 op-no-print">
    <form method="GET" class="row g-2 align-items-end op-filters">
        <div class="col-md-2 col-6"><label class="form-label small">{{ $meta['date_label'] }} from</label><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control"></div>
        <div class="col-md-2 col-6"><label class="form-label small">To</label><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control"></div>
        <div class="col-md-2 col-6"><label class="form-label small">Department</label>
            <select name="department" class="form-select"><option value="">All</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department'] ?? null) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-3 col-6"><label class="form-label small">Employee</label>
            <select name="employee" class="form-select"><option value="">All</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(($filters['employee'] ?? null) == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6"><label class="form-label small">Status</label>
            <select name="status" class="form-select"><option value="">All</option>@foreach ($meta['statuses'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? null) === $s)>{{ label($s) }}</option>@endforeach</select></div>
        <div class="col-md-1 col-6 d-flex gap-1"><button class="btn btn-primary flex-fill" title="Apply"><i class="bi bi-funnel"></i></button>@if(request()->query())<a href="{{ route('reports.show', $type) }}" class="btn btn-light btn-icon" title="Reset"><i class="bi bi-x-lg"></i></a>@endif</div>
    </form>
</x-card>

<div class="row g-3 mb-4">
    @foreach ($summary as $label => $value)
        <div class="col-6 col-lg"><div class="op-card p-3 h-100"><div class="small text-muted">{{ $label }}</div><div class="h4 mb-0 mt-1">{{ $value }}</div></div></div>
    @endforeach
</div>

<x-card :padding="false" :title="$records->total().' record(s)'">
    @if ($records->isEmpty())
        <x-empty-state icon="bi-search" title="No data for these filters" />
    @else
        <x-table>
            <thead><tr>@foreach ($columns as $key => $label)<th class="{{ in_array($key, $numeric) ? 'text-end' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($records as $model)
                @php($row = $service->row($type, $model))
                <tr>@foreach ($columns as $key => $label)<td data-label="{{ $label }}" class="{{ in_array($key, $numeric) ? 'text-end' : '' }}">@if ($key === 'status')<x-status-badge :status="Str::snake(strtolower($row[$key]))" :label="$row[$key]" />@else{{ $row[$key] }}@endif</td>@endforeach</tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $records->links() }}
    @endif
</x-card>
@endsection
