@props(['config', 'height' => null])
<div {{ $attributes->class(['op-chart']) }} @if($height) style="height: {{ $height }}px" @endif>
    <canvas data-chart='@json($config)' data-currency="{{ setting('currency_symbol') }}" role="img" aria-label="Chart"></canvas>
</div>
@once
    @push('scripts')
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script src="{{ asset('js/charts.js') }}?v={{ filemtime(public_path('js/charts.js')) }}"></script>
    @endpush
@endonce
