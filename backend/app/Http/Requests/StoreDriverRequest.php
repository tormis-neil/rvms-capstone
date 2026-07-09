<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin-added driver (FR-06) — created 'active' immediately, unlike
 * self-registration (FR-03) which starts 'pending'.
 */
class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry_date' => ['nullable', 'date'],
            'assigned_vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')
                    ->where('agency_id', $agencyId)
                    ->whereNull('assigned_driver_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
            'assigned_vehicle_id.exists' => 'That vehicle is not available to assign (it may already have a driver).',
        ];
    }
}
