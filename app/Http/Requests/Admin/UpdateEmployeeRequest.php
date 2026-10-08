<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'string', Rule::in(['Male', 'Female', 'Other'])],
            'mobile' => ['required', 'digits:10', Rule::unique('employees', 'mobile')->ignore($this->route('employee')?->profile?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('employee'))],
            'designation_id' => ['required', 'exists:employee_designations,id'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
