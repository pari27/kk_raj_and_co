<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['sometimes', 'string', Rule::in(['revenue', 'payments', 'staff-performance', 'client-history', 'open-tickets', 'pending-documents', 'gst-summary'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Employee->value)],
            'status' => ['nullable', 'string', Rule::in(array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::cases()))],
            'mode' => ['nullable', 'string', 'max:50'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
            'client_id' => ['nullable', 'integer', 'exists:customers,id'],
        ];
    }
}
