<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $meta['title'] }}</title>
<style>
    @page { margin: 26px 28px 40px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 9px; color: #1e293b; }
    .head { border-bottom: 3px solid #4f46e5; padding-bottom: 10px; margin-bottom: 12px; }
    .brand { color: #4f46e5; font-size: 9px; font-weight: bold; letter-spacing: 2px; }
    h1 { font-size: 17px; margin: 3px 0; }
    .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; }
    .summary td { background: #f1f5f9; padding: 7px 9px; border: 2px solid #fff; }
    .summary .v { font-size: 12px; font-weight: bold; }
    .data th { background: #4f46e5; color: #fff; text-align: left; padding: 6px; font-size: 8px; text-transform: uppercase; }
    .data td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; }
    .data tr:nth-child(even) td { background: #f8fafc; }
    .r { text-align: right; }
    .footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="footer">{{ setting('company_name') }} · {{ $meta['title'] }} · Generated {{ now()->format('M d, Y h:i A') }} by OfficePro</div>
<table class="head"><tr>
    <td><div class="brand">OFFICEPRO</div><h1>{{ $meta['title'] }}</h1><div class="muted">{{ setting('company_name') }} · {{ setting('company_address') }}</div></td>
    <td class="r muted">@foreach ($filters as $k => $v)<div><strong>{{ $k }}:</strong> {{ $v }}</div>@endforeach</td>
</tr></table>
<table class="summary" style="margin-bottom:12px"><tr>
    @foreach ($summary as $label => $value)<td><div class="muted">{{ $label }}</div><div class="v">{{ $value }}</div></td>@endforeach
</tr></table>
<table class="data">
    <thead><tr>@foreach ($columns as $key => $label)<th class="{{ in_array($key, $numeric) ? 'r' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>@foreach ($columns as $key => $label)<td class="{{ in_array($key, $numeric) ? 'r' : '' }}">{{ $row[$key] }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="{{ count($columns) }}" style="text-align:center; padding: 20px">No records for the selected filters.</td></tr>
    @endforelse
    </tbody>
</table>
@if (count($rows) >= 2000)<p class="muted">Showing the first 2,000 rows. Use CSV export for the full data set.</p>@endif
</body>
</html>
