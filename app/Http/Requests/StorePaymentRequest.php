<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
        return [
            'enquiry_id' => ['required', 'exists:enquiries,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'mode' => ['required', Rule::in(['UPI', 'Bank transfer', 'Cash', 'Cheque'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
