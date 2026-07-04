<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating/updating a vehicle (FR-05). The assigned driver
 * must belong to the caller's own agency (agency isolation, FR-02).
 */
class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;
        $agencyId = $this->user()->agency_id;

        return [
            'type' => ['required', 'string', 'max:100'],
            'plate_number' => [
                'required', 'string', 'max:20',
                Rule::unique('vehicles', 'plate_number')
                    ->where(fn ($q) => $q->where('agency_id', $agencyId))
                    ->ignore($vehicleId),
            ],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(Vehicle::STATUSES)],
            'assigned_driver_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('agency_id', $agencyId)
                    ->where('role', User::ROLE_DRIVER)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.unique' => 'A vehicle with this plate number already exists in your agency.',
            'status.in' => 'The status must be one of: '.implode(', ', Vehicle::STATUSES).'.',
            'assigned_driver_id.exists' => 'The assigned driver must be a driver in your agency.',
            'current_mileage.min' => 'The current mileage cannot be negative.',
        ];
    }
}
