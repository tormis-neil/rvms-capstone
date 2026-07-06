<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for an admin adding/updating a driver record (FR-06).
 */
class DriverRequest extends FormRequest
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
        $driverId = $this->route('driver')?->id;
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($driverId),
            ],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry_date' => ['nullable', 'date'],
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')
                    ->where(fn ($q) => $q->where('agency_id', $this->user()->agency_id)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists.',
        ];
    }
}
