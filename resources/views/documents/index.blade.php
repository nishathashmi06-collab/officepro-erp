@extends('layouts.app')
@section('title', 'Documents')
@section('content')
<x-page-header title="Documents" subtitle="Securely stored contracts, IDs, certificates and company policies." :breadcrumbs="['Documents' => null]">
    <x-slot:actions>
        @can('create', App\Models\Document::class)<a href="{{ route('documents.create') }}" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload document</a>@endcan
    </x-slot:actions>
</x-page-header>

@if ($stats['expired'] || $stats['expiring'])
    <x-alert type="warning" :dismissible="false">
        <strong>{{ $stats['expiring'] }}</strong> document(s) expire within {{ $expiryDays }} days and <strong>{{ $stats['expired'] }}</strong> have already expired.
        <a href="{{ route('documents.index', ['expiry' => 'expiring']) }}" class="fw-semibold ms-1">Review expiring</a> ·
        <a href="{{ route('documents.index', ['expiry' => 'expired']) }}" class="fw-semibold">Review expired</a>
    </x-alert>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4 op-animate op-animate-1"><x-stat-card label="Documents" :value="$stats['total']" icon="bi-folder2-open" color="primary" :href="route('documents.index')" /></div>
    <div class="col-md-4 op-animate op-animate-2"><x-stat-card label="Expiring soon" :value="$stats['expiring']" icon="bi-hourglass-split" color="warning" :meta="'Within '.$expiryDays.' days'" :href="route('documents.index', ['expiry' => 'expiring'])" /></div>
    <div class="col-md-4 op-animate op-animate-3"><x-stat-card label="Expired" :value="$stats['expired']" icon="bi-exclamation-octagon" color="danger" :href="route('documents.index', ['expiry' => 'expired'])" /></div>
</div>

<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom row g-2 align-items-end op-filters mx-0">
        <div class="col-md-3"><label class="form-label small">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Title or file name"></div>
        <div class="col-md-2 col-6"><label class="form-label small">Category</label>
            <select name="category" class="form-select"><option value="">All</option>@foreach (App\Models\Document::CATEGORIES as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ label($c) }}</option>@endforeach</select></div>
        @if ($employees->isNotEmpty())
            <div class="col-md-3 col-6"><label class="form-label small">Employee</label>
                <select name="employee" class="form-select"><option value="">All</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('employee') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
        @endif
        <div class="col-md-2 col-6"><label class="form-label small">Expiry</label>
            <select name="expiry" class="form-select"><option value="">Any</option><option value="expiring" @selected(request('expiry') === 'expiring')>Expiring soon</option><option value="expired" @selected(request('expiry') === 'expired')>Expired</option></select></div>
        <div class="col-md-2 col-6 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i></button>@if(request()->query())<a href="{{ route('documents.index') }}" class="btn btn-light btn-icon"><i class="bi bi-x-lg"></i></a>@endif</div>
    </form>
    @if ($documents->isEmpty())
        <x-empty-state icon="bi-folder2-open" title="No documents found" />
    @else
        <x-table>
            <thead><tr><th>Document</th><th>Category</th><th>Employee</th><th>Expiry</th><th>Uploaded</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($documents as $doc)
                @php($state = $doc->expiryState())
                <tr>
                    <td data-label="Document">
                        <div class="d-flex align-items-center gap-2">
                            <span class="op-list-icon op-soft-{{ str_contains((string) $doc->mime_type, 'pdf') ? 'danger' : (str_contains((string) $doc->mime_type, 'image') ? 'info' : 'primary') }}"><i class="bi bi-file-earmark{{ str_contains((string) $doc->mime_type, 'pdf') ? '-pdf' : (str_contains((string) $doc->mime_type, 'image') ? '-image' : '-text') }}"></i></span>
                            <div class="min-w-0"><div class="fw-semibold text-truncate">{{ $doc->title }}</div><div class="small text-muted text-truncate">{{ $doc->original_name }} · {{ $doc->readable_size }} @unless($doc->employee_visible)<span class="op-badge op-soft-secondary no-dot ms-1"><i class="bi bi-lock"></i> HR only</span>@endunless</div></div>
                        </div>
                    </td>
                    <td data-label="Category">{{ label($doc->category) }}</td>
                    <td data-label="Employee">{{ $doc->employee?->full_name ?? 'Company-wide' }}</td>
                    <td data-label="Expiry">{{ fmt_date($doc->expiry_date) }} @if($state && $state !== 'valid')<div><x-status-badge :status="$state" /></div>@endif</td>
                    <td data-label="Uploaded" class="small">{{ $doc->created_at->format('M d, Y') }}<div class="text-muted">{{ $doc->uploader?->name }}</div></td>
                    <td class="actions" data-label="">
                        <a href="{{ route('documents.download', [$doc, 'inline' => 1]) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm btn-icon" title="Preview"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-soft-primary btn-sm btn-icon" title="Download"><i class="bi bi-download"></i></a>
                        @can('update', $doc)<a href="{{ route('documents.edit', $doc) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                        @can('delete', $doc)<x-delete-button :action="route('documents.destroy', $doc)" title="Delete this document?" message="The file will be permanently removed from storage." />@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $documents->links() }}
    @endif
</x-card>
@endsection
