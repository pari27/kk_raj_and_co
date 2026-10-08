<?php

namespace App\Http\Requests;

use App\Models\ServiceDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketDocumentRequest extends FormRequest
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
        $serviceDocument = ServiceDocument::find($this->input('service_document_id'));

        $fileRules = ['required', 'file'];

        if ($serviceDocument?->max_file_size_kb) {
            $fileRules[] = 'max:'.$serviceDocument->max_file_size_kb;
        }

        $extensions = collect(explode(',', (string) $serviceDocument?->allowed_formats))
            ->map(fn ($format) => strtolower(trim($format)))
            ->filter()
            ->implode(',');

        if ($extensions !== '') {
            $fileRules[] = 'mimes:'.$extensions;
        }

        return [
            'service_document_id' => ['required', 'exists:service_documents,id'],
            'file' => $fileRules,
        ];
    }
}
