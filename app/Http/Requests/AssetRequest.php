<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assets.manage');
    }

    public function rules(): array
    {
        $id = $this->route('asset')?->id;

        return [
            'asset_code' => ['nullable', 'string', 'max:30', Rule::unique('assets', 'asset_code')->ignore($id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(Asset::CATEGORIES)],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('assets', 'serial_number')->ignore($id)],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'retired'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
