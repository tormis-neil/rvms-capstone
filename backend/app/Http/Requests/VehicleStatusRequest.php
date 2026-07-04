<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for PATCH /vehicles/{id}/status — the single shared status
 * field only ever accepts the four operational values (FR-18).
 */
class VehicleStatusRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::in(Vehicle::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The status must be one of: '.implode(', ', Vehicle::STATUSES).'.',
        ];
    }
}
