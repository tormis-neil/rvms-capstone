<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Manual vehicle status change (FR-18) — only the four enum values are
 * accepted; anything else is a 422.
 */
class UpdateVehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Vehicle::STATUSES)],
            // Required since 2026-08 (adviser consultation) — see the note on
            // the web twin in Web\VehicleController::updateStatus. Both manual
            // paths demand it, so the rule cannot be sidestepped by calling the
            // API instead of using the dashboard.
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: '.implode(', ', Vehicle::STATUSES).'.',
            'remarks.required' => 'Give a reason for the status change.',
        ];
    }
}
