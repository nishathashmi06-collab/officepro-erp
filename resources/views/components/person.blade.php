@props(['employee' => null, 'href' => null, 'size' => 36, 'sub' => null])
@if ($employee)
    @php($tag = $href ? 'a' : 'div')
    <{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['op-person']) }}>
        <x-avatar :name="$employee->full_name" :src="$employee->photo_url" :size="$size" />
        <span class="min-w-0">
            <span class="name text-truncate">{{ $employee->full_name }}</span>
            <span class="sub d-block text-truncate">{{ $sub ?? $employee->employee_code }}</span>
        </span>
    </{{ $tag }}>
@else
    <span class="text-muted">—</span>
@endif
