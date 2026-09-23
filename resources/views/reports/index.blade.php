@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<x-page-header title="Reports" subtitle="Live reports built from your OfficePro data — filter, print or export to PDF and CSV." :breadcrumbs="['Reports' => null]" />
<div class="row g-3">
    @forelse ($types as $key => $t)
        <div class="col-md-6 col-xl-3 op-animate op-animate-{{ min($loop->iteration, 6) }}">
            <a href="{{ route('reports.show', $key) }}" class="op-card op-card-hover d-block p-4 h-100 text-reset">
                <span class="op-list-icon op-soft-{{ $t['color'] }} mb-3" style="width:50px;height:50px;font-size:1.35rem"><i class="bi {{ $t['icon'] }}"></i></span>
                <h3 class="h6 mb-1">{{ $t['title'] }}</h3>
                <p class="small text-muted mb-3">{{ $t['description'] }}</p>
                <span class="small fw-semibold text-primary">Open report <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    @empty
        <div class="col-12"><x-card><x-empty-state icon="bi-bar-chart" title="No reports available" message="Your role does not include access to any reports." /></x-card></div>
    @endforelse
</div>
@endsection
