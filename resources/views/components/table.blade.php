@props(['stack' => true])
<div class="op-table-wrap">
    <table {{ $attributes->class(['table op-table table-hover align-middle', 'op-table-stack' => $stack]) }}>
        {{ $slot }}
    </table>
</div>
