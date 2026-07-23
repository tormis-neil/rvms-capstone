<?php

namespace App\Http\Requests;

use App\Models\Dispatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Open / edit a dispatch (FR-15). The vehicle and driver must belong to the
 * admin's own agency; "Others" mission requires the free-text detail; the
 * time-out odometer is optional (digitizes the paper form's odometer field).
 */
class StoreDispatchRequest extends FormRequest
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
                'required',
                Rule::exists('users', 'id')->where('agency_id', $agencyId)->where('role', 'driver'),
            ],
            'mission_type' => ['required', Rule::in(Dispatch::MISSION_TYPES)],
            'mission_other' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $this->input('mission_type') === Dispatch::MISSION_OTHERS),
            ],
            'location' => ['required', 'string', 'max:255'],
            'time_out' => ['required', 'date'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'mission_other.required' => 'Please specify the mission when the type is "Others".',
        ];
    }
}
