@props(['name', 'label', 'checked' => false, 'value' => '1', 'switch' => true, 'help' => null])
@php($id = $attributes->get('id', 'f_'.str_replace(['[', ']', '.'], '_', $name)))
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))
<div {{ $attributes->only('class')->class(['form-check mb-3', 'form-switch' => $switch]) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input class="form-check-input @error($errorKey) is-invalid @enderror" type="checkbox" role="{{ $switch ? 'switch' : 'checkbox' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
           @checked(old($errorKey, $checked ? $value : null) == $value) {{ $attributes->except(['class', 'id']) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($help)<div class="form-text mt-0">{{ $help }}</div>@endif
    @error($errorKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
