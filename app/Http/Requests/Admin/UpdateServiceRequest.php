<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')->ignore($service->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'default_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'price_includes_gst' => ['nullable', 'boolean'],
            'documents' => ['nullable', 'array'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.instructions' => ['nullable', 'string'],
            'documents.*.allowed_formats' => ['nullable', 'string', 'max:255'],
            'documents.*.max_file_size_mb' => ['nullable', 'numeric', 'min:0.1'],
            'documents.*.mandatory' => ['nullable', 'boolean'],
        ];
    }
}
