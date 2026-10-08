<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
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
        $hasExistingCustomer = $this->filled('customer_id');
        $assignableRoles = [UserRole::Employee->value];

        if ($this->user()?->isAdmin() || $this->user()?->isSuperAdmin()) {
            $assignableRoles[] = UserRole::Admin->value;
            $assignableRoles[] = UserRole::SuperAdmin->value;
        }

        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_phone' => [
                Rule::requiredIf(! $hasExistingCustomer),
                'digits:10',
                $hasExistingCustomer ? 'nullable' : Rule::unique('customers', 'phone'),
            ],
            'customer_name' => [Rule::requiredIf(! $hasExistingCustomer), 'nullable', 'string', 'max:150'],
            'customer_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($this->input('customer_id')),
            ],
            'customer_email_notifications' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'distinct', 'exists:services,id'],
            'services.*.price' => ['nullable', 'numeric', 'min:0'],
            'services.*.assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->whereIn('role', $assignableRoles)
                    ->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_email.unique' => 'This email is already registered to another client.',
            'customer_phone.unique' => 'This mobile number is already registered to another client.',
        ];
    }
}
