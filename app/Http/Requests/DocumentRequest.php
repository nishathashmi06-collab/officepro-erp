<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document ? $this->user()->can('update', $document) : $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(Document::CATEGORIES)],
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'expiry_date' => ['nullable', 'date'],
            'employee_visible' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [
                $this->route('document') ? 'nullable' : 'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,zip',
            ],
        ];
    }

    public function messages(): array
    {
        return ['file.mimes' => 'Allowed file types: PDF, Office documents, text/CSV, images and ZIP.'];
    }

    public function attributes(): array
    {
        return ['employee_id' => 'employee'];
    }
}
