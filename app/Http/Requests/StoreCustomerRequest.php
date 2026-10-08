<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
        $emailRules = ['nullable', 'email', 'max:255'];
        if ($this->routeIs('customers.store')) {
            $emailRules = ['required', 'email', 'max:255', Rule::unique('customers', 'email')];
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'digits:10'],
            'email' => $emailRules,
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
        ];
    }
}
