<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vehicle create/update rules (FR-05). Plate is unique per agency; the
 * assigned driver must be a driver of the admin's own agency (FR-02).
 */
class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role gating happens in the route middleware
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'type' => ['required', 'string', 'max:100'],
            'plate_number' => [
                'required', 'string', 'max:20',
                Rule::unique('vehicles', 'plate_number')
                    ->where('agency_id', $agencyId)
                    ->ignore($vehicleId),
            ],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'assigned_driver_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('agency_id', $agencyId)
                    ->where('role', User::ROLE_DRIVER),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.unique' => 'A vehicle with this plate number already exists in your agency.',
            'assigned_driver_id.exists' => 'The assigned driver must be an authorized driver of your agency.',
        ];
    }
}
