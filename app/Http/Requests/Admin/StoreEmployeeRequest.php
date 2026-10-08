<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            'mobile' => ['required', 'digits:10', 'unique:employees,mobile'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'designation_id' => ['required', 'exists:employee_designations,id'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
