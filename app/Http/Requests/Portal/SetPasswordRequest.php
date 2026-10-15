<?php

namespace App\Http\Requests\Portal;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SetPasswordRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    /**
     * The password may not be the client's own email or phone number.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $customer = Customer::query()->find($this->session()->get('portal_otp_verified.customer_id'));
            $password = mb_strtolower((string) $this->input('password'));

            if ($customer && in_array($password, array_filter([mb_strtolower((string) $customer->email), (string) $customer->phone]), true)) {
                $validator->errors()->add('password', "Your password can't be your email or mobile number.");
            }
        });
    }
}
