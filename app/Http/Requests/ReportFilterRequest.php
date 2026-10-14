<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Services\ReportService;
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
            'report' => ['sometimes', 'string', Rule::in(array_keys(ReportService::REPORTS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'as_on' => ['nullable', 'date'],
            'fy' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'designation_id' => ['nullable', 'integer', 'exists:employee_designations,id'],
            'status' => ['nullable', 'string', Rule::in(array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::cases()))],
            'mode' => ['nullable', 'string', Rule::in(ReportService::MODES)],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'enquiry_status' => ['nullable', 'string', Rule::in(['Open', 'Closed'])],
            'client_id' => ['nullable', 'integer', 'exists:customers,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
