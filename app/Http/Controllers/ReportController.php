<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        return view('reports.index', ['types' => ReportService::allowedTypes($request->user())]);
    }

    public function show(Request $request, string $type): View
    {
        $meta = $this->authorizeType($request, $type);
        $filters = $this->filters($request, $type);

        return view('reports.show', [
            'type' => $type,
            'meta' => $meta,
            'filters' => $filters,
            'columns' => $this->reports->columns($type),
            'numeric' => $this->reports->numericColumns($type),
            'records' => $this->reports->query($type, $filters)->paginate(25)->withQueryString(),
            'summary' => $this->reports->summary($type, $filters),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'service' => $this->reports,
        ]);
    }

    public function export(Request $request, string $type, string $format): StreamedResponse|Response
    {
        $meta = $this->authorizeType($request, $type);
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $filters = $this->filters($request, $type);
        $columns = $this->reports->columns($type);
        $filename = $type.'-report-'.now()->format('Y-m-d-His');

        activity('exported', 'reports', "{$request->user()->name} exported {$meta['title']} as ".strtoupper($format));

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($type, $filters, $columns) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
                fputcsv($out, array_values($columns));
                $this->reports->query($type, $filters)->chunk(500, function ($chunk) use ($out, $type) {
                    foreach ($chunk as $model) {
                        fputcsv($out, array_map(fn ($v) => $this->csvSafe($v), array_values($this->reports->row($type, $model))));
                    }
                });
                fclose($out);
            }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $rows = $this->reports->query($type, $filters)->limit(2000)->get()->map(fn ($m) => $this->reports->row($type, $m));

        return Pdf::loadView('reports.pdf', [
            'meta' => $meta,
            'columns' => $columns,
            'numeric' => $this->reports->numericColumns($type),
            'rows' => $rows,
            'summary' => $this->reports->summary($type, $filters),
            'filters' => $this->describeFilters($filters),
        ])->setPaper('a4', count($columns) > 7 ? 'landscape' : 'portrait')->download($filename.'.pdf');
    }

    private function authorizeType(Request $request, string $type): array
    {
        $types = ReportService::types();
        abort_unless(isset($types[$type]), 404);
        abort_unless($request->user()->hasPermission($types[$type]['permission']), 403);

        return $types[$type];
    }

    private function filters(Request $request, string $type): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
            'employee' => ['nullable', 'integer', 'exists:employees,id'],
            'status' => ['nullable', 'in:'.implode(',', ReportService::types()[$type]['statuses'])],
        ]);
    }

    private function describeFilters(array $f): array
    {
        return array_filter([
            'Period' => ($f['from'] ?? null) || ($f['to'] ?? null) ? fmt_date($f['from'] ?? null).' – '.fmt_date($f['to'] ?? null) : 'All time',
            'Department' => isset($f['department']) ? Department::find($f['department'])?->name : null,
            'Employee' => isset($f['employee']) ? Employee::find($f['employee'])?->full_name : null,
            'Status' => isset($f['status']) ? label($f['status']) : null,
        ]);
    }

    /** Prevent CSV/formula injection when the file is opened in a spreadsheet. */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
