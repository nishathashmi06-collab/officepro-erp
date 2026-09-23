<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $expiryDays = (int) setting('document_expiry_days', 30);

        $documents = Document::visibleTo($user)
            ->with(['employee', 'uploader'])
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('original_name', 'like', "%{$v}%")))
            ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->query('employee'), fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->query('scope') === 'company', fn ($q) => $q->whereNull('employee_id'))
            ->when($request->query('expiry') === 'expiring', fn ($q) => $q->whereNotNull('expiry_date')->whereBetween('expiry_date', [today()->toDateString(), today()->addDays($expiryDays)->toDateString()]))
            ->when($request->query('expiry') === 'expired', fn ($q) => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $base = Document::visibleTo($user);

        return view('documents.index', [
            'documents' => $documents,
            'expiryDays' => $expiryDays,
            'stats' => [
                'total' => (clone $base)->count(),
                'expiring' => (clone $base)->whereNotNull('expiry_date')->whereBetween('expiry_date', [today()->toDateString(), today()->addDays($expiryDays)->toDateString()])->count(),
                'expired' => (clone $base)->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())->count(),
            ],
            'employees' => $user->hasPermission('documents.view_all') ? Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']) : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Document::class);

        return view('documents.form', [
            'document' => new Document(['employee_id' => $request->query('employee'), 'employee_visible' => true, 'category' => 'employee_document']),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $data['employee_visible'] = $request->boolean('employee_visible');
        $data['uploaded_by'] = $request->user()->id;
        $document = Document::create($data + $this->storeFile($request->file('file')));

        activity('uploaded', 'documents', "Uploaded document \"{$document->title}\"".($document->employee ? " for {$document->employee->full_name}" : ''), $document);

        return redirect()->route('documents.index')->with('success', 'Document uploaded securely.');
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        return view('documents.form', [
            'document' => $document,
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']),
        ]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $data['employee_visible'] = $request->boolean('employee_visible');

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($document->file_path);
            $data += $this->storeFile($request->file('file'));
        }

        // A new expiry date should trigger a fresh reminder.
        if ($document->expiry_date?->toDateString() !== ($data['expiry_date'] ?? null)) {
            $data['expiry_notified_at'] = null;
        }

        $document->update($data);
        activity('updated', 'documents', "Updated document \"{$document->title}\"", $document);

        return redirect()->route('documents.index')->with('success', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
        activity('deleted', 'documents', "Deleted document \"{$document->title}\"", $document);

        return back()->with('success', 'Document deleted.');
    }

    /** Files live on the private disk and are streamed only after authorisation. */
    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'The file could not be found.');

        activity('downloaded', 'documents', "{$request->user()->name} downloaded \"{$document->title}\"", $document);

        return $request->boolean('inline')
            ? Storage::disk('local')->response($document->file_path, $document->original_name)
            : Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    private function storeFile(UploadedFile $file): array
    {
        return [
            // store() generates a random, safe file name; the original is kept for display only.
            'file_path' => $file->store('documents', 'local'),
            'original_name' => mb_substr(preg_replace('/[^\w.\- ()]/u', '_', $file->getClientOriginalName()), 0, 200),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}
