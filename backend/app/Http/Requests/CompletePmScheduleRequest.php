<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesRepairReceipt;
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
    use ValidatesRepairReceipt;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_serviced' => ['required', 'date'],
            // The odometer AT THE TIME OF SERVICE (FR-14, 2026-08). Optional
            // because a time-based schedule has no mileage to record, and
            // because the reading is not always to hand when the paperwork is
            // filed late. Recording it is what stops each cycle's target
            // drifting later than the last.
            'completion_mileage' => ['nullable', 'integer', 'min:0'],
            'completion_repair_source' => ['required', Rule::in(RepairLog::SOURCES)],
            // Same rule, same wording as StoreRepairLogRequest: the two modules
            // record the same fact from the same three sources, so they must
            // demand the shop name identically.
            'completion_external_shop_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->input('completion_repair_source') === RepairLog::SOURCE_EXTERNAL),
            ],
            // Same evidence rule as a repair log, from the shared trait: the
            // two modules record the same fact from the same three sources.
            'completion_receipt' => $this->receiptRules(
                'completion_repair_source',
                $this->route('pmSchedule')?->completion_receipt_path,
            ),
            'completion_parts_replaced' => ['nullable', 'string'],
            'completion_remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'completion_external_shop_name.required' => 'The shop name is required when the source is an external repair shop.',
            ...$this->receiptMessages('completion_receipt'),
        ];
    }
}
