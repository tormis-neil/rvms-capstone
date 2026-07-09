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
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: '.implode(', ', Vehicle::STATUSES).'.',
        ];
    }
}
