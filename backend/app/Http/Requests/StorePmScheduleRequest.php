<?php

namespace App\Http\Requests;

use App\Models\PmSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PM schedule create/update rules (FR-14). The vehicle must belong to the
 * admin's own agency. Mileage-based schedules require the km fields;
 * time-based schedules require a due date. Due-Soon thresholds are stored per
 * schedule (configurable, never constants).
 */
class StorePmScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;
        $isMileage = $this->input('pm_type') === PmSchedule::TYPE_MILEAGE;
        $isTime = $this->input('pm_type') === PmSchedule::TYPE_TIME;

        return [
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'service_target' => ['required', 'string', 'max:255'],
            'pm_type' => ['required', Rule::in([PmSchedule::TYPE_MILEAGE, PmSchedule::TYPE_TIME])],

            // Mileage-based
            'interval_km' => [Rule::requiredIf($isMileage), 'nullable', 'integer', 'min:1'],
            'last_pm_mileage' => [Rule::requiredIf($isMileage), 'nullable', 'integer', 'min:0'],
            'due_soon_threshold_km' => [Rule::requiredIf($isMileage), 'nullable', 'integer', 'min:0'],

            // Time-based
            'due_date' => [Rule::requiredIf($isTime), 'nullable', 'date'],
            'due_soon_threshold_days' => [Rule::requiredIf($isTime), 'nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'interval_km.required' => 'A mileage interval is required for a mileage-based schedule.',
            'last_pm_mileage.required' => 'The last-service mileage is required for a mileage-based schedule.',
            'due_soon_threshold_km.required' => 'A Due-Soon km threshold is required for a mileage-based schedule.',
            'due_date.required' => 'A due date is required for a time-based schedule.',
            'due_soon_threshold_days.required' => 'A Due-Soon days threshold is required for a time-based schedule.',
        ];
    }
}
