@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'prefix' => null, 'suffix' => null])
@php($id = $attributes->get('id', 'f_'.str_replace(['[', ']', '.'], '_', $name)))
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))
<div {{ $attributes->only('class')->class(['mb-3']) }}>
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="req">*</span>@endif</label>@endif
    @if ($prefix || $suffix)<div class="input-group has-validation">@endif
    @if ($prefix)<span class="input-group-text">{{ $prefix }}</span>@endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           @if ($type !== 'file' && $type !== 'password') value="{{ old($errorKey, $value) }}" @endif
           {{ $attributes->except(['class', 'id'])->class(['form-control', 'is-invalid' => $errors->has($errorKey)]) }}
           @if ($required) required @endif>
    @if ($suffix)<span class="input-group-text">{{ $suffix }}</span>@endif
    @error($errorKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($prefix || $suffix)</div>@endif
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
