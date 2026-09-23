@extends('layouts.app')
@php($editing = $document->exists)
@section('title', $editing ? 'Edit document' : 'Upload document')
@section('content')
<x-page-header :title="$editing ? 'Edit document' : 'Upload document'" subtitle="Files are stored privately and only served to authorised users." :breadcrumbs="['Documents' => route('documents.index'), ($editing ? 'Edit' : 'Upload') => null]" />
<div class="row"><div class="col-xl-8">
<x-card>
    <form method="POST" action="{{ $editing ? route('documents.update', $document) : route('documents.store') }}" enctype="multipart/form-data" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <x-form.input name="title" label="Title" :value="$document->title" required />
        <div class="row">
            <x-form.select class="col-md-6" name="category" label="Category" :options="collect(App\Models\Document::CATEGORIES)->mapWithKeys(fn ($c) => [$c => label($c)])" :value="$document->category" required />
            <x-form.select class="col-md-6" name="employee_id" label="Employee" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.')'])" :value="$document->employee_id" placeholder="— Company-wide document —" />
            <x-form.input class="col-md-6" name="expiry_date" type="date" label="Expiry date" :value="$document->expiry_date?->toDateString()" help="Optional. Used for expiry warnings." />
        </div>
        <x-form.textarea name="description" label="Description" :value="$document->description" rows="2" />
        <x-form.input name="file" type="file" :label="$editing ? 'Replace file' : 'File'" :required="! $editing" help="PDF, Office, text/CSV, images or ZIP · max 10 MB" />
        @if ($editing)<p class="small text-muted mt-n2">Current: {{ $document->original_name }} ({{ $document->readable_size }})</p>@endif
        <x-form.checkbox name="employee_visible" label="Visible to the employee (or to all staff for company-wide documents)" :checked="$document->employee_visible" />
        <x-form.actions :cancel="route('documents.index')" :submit="$editing ? 'Save changes' : 'Upload'" />
    </form>
</x-card>
</div></div>
@endsection
