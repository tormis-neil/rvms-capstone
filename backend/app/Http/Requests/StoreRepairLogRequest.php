<?php

namespace App\Http\Requests;

use App\Models\RepairLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Repair-log create/update rules (FR-13). The vehicle (and optional driver)
 * must belong to the admin's own agency; when the source is External Repair
 * Shop, the shop name is required.
 */
class StoreRepairLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'driver_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('agency_id', $agencyId)->where('role', 'driver'),
            ],
            'repair_date' => ['required', 'date'],
            'scope_of_work' => ['required', 'string'],
            'parts_replaced' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'repair_source' => ['required', Rule::in(RepairLog::SOURCES)],
            'external_shop_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->input('repair_source') === RepairLog::SOURCE_EXTERNAL),
            ],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'external_shop_name.required' => 'The shop name is required when the source is an external repair shop.',
        ];
    }
}
