<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One more service for an enquiry that is still open. Admin only (see EnquiryPolicy::addService()).
 */
class AddEnquiryServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addService', $this->route('enquiry')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where('is_active', true),
                // The enquiry form allows each service once, so adding one later follows the same rule.
                Rule::notIn($this->route('enquiry')->tickets()->pluck('service_id')->all()),
            ],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->whereIn('role', [UserRole::Admin->value, UserRole::Employee->value])
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
            'service_id.required' => 'Choose the service to add.',
            'service_id.exists' => 'That service is not available.',
            'service_id.not_in' => 'This service is already in this enquiry.',
        ];
    }
}
