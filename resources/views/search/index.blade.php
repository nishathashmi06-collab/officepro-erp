@extends('layouts.app')
@section('title', 'Search')
@section('content')
<x-page-header title="Search" :subtitle="$term ? $total.' result(s) for “'.$term.'”' : 'Search across employees, tasks, departments, documents and assets.'" :breadcrumbs="['Search' => null]" />
<x-card class="mb-4">
    <form method="GET" action="{{ route('search') }}" class="d-flex gap-2">
        <input type="search" name="q" value="{{ $term }}" class="form-control form-control-lg" placeholder="Type at least 2 characters…" autofocus minlength="2">
        <button class="btn btn-primary btn-lg"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
    </form>
</x-card>
@if ($term && mb_strlen($term) < 2)
    <x-alert type="warning" :dismissible="false">Please enter at least 2 characters.</x-alert>
@elseif ($term && empty($groups))
    <x-card><x-empty-state icon="bi-search" title="No results" message="Nothing matched “{{ $term }}”. Try a different spelling or fewer words." /></x-card>
@endif
<div class="row g-4">
    @foreach ($groups as $name => $items)
        <div class="col-lg-6">
            <x-card :title="$name" :subtitle="$items->count().' match(es)'" :padding="false">
                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}" class="op-list-item">
                        <span class="op-list-icon op-soft-primary"><i class="bi {{ $item['icon'] }}"></i></span>
                        <div class="min-w-0"><div class="fw-semibold text-truncate">{{ $item['title'] }}</div><div class="small text-muted text-truncate">{{ $item['subtitle'] }}</div></div>
                    </a>
                @endforeach
            </x-card>
        </div>
    @endforeach
</div>
@endsection
