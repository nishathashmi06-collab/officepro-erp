@props(['column', 'label', 'sort' => null, 'direction' => 'asc'])
@php($active = $sort === $column)
<a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $active && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}">
    {{ $label }}
    @if ($active)<i class="bi bi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@else<i class="bi bi-arrow-down-up opacity-50"></i>@endif
</a>
