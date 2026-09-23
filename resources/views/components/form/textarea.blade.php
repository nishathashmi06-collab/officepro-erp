@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3, 'help' => null])
@php($id = $attributes->get('id', 'f_'.$name))
<div {{ $attributes->only('class')->class(['mb-3']) }}>
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="req">*</span>@endif</label>@endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" {{ $attributes->except(['class', 'id'])->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @if ($required) required @endif>{{ old($name, $value) }}</textarea>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
