@props(['name', 'label' => null, 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'help' => null])
@php($id = $attributes->get('id', 'f_'.str_replace(['[', ']', '.'], '_', $name)))
@php($current = (string) old($name, $value))
<div {{ $attributes->only('class')->class(['mb-3']) }}>
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="req">*</span>@endif</label>@endif
    <select name="{{ $name }}" id="{{ $id }}" {{ $attributes->except(['class', 'id'])->class(['form-select', 'is-invalid' => $errors->has($name)]) }} @if ($required) required @endif>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($current !== '' && $current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
