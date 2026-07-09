<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin edit of a driver record (FR-06). Password is optional (leave blank
 * to keep current). The vehicle select defaults to "no change" and can only
 * ever pick a vehicle that is unassigned or already this driver's own — it
 * can never steal a vehicle belonging to another driver.
 */
class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;
        $driverId = $this->route('driver')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($driverId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry_date' => ['nullable', 'date'],
            'assigned_vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($agencyId, $driverId) {
                    $query->where('agency_id', $agencyId)
                        ->where(function ($q) use ($driverId) {
                            $q->whereNull('assigned_driver_id')->orWhere('assigned_driver_id', $driverId);
                        });
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
            'assigned_vehicle_id.exists' => 'That vehicle is not available to assign (it may already have a different driver).',
        ];
    }
}
