<?php

namespace App\Http\Requests;

use App\Models\RepairLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PM completion (FR-14). Records the service date, repair source, parts, and
 * remarks. Completing NEVER auto-creates the next cycle — each cycle is entered
 * deliberately (design decision 3).
 */
class CompletePmScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_serviced' => ['required', 'date'],
            'completion_repair_source' => ['required', Rule::in(RepairLog::SOURCES)],
            'completion_parts_replaced' => ['nullable', 'string'],
            'completion_remarks' => ['nullable', 'string'],
        ];
    }
}
