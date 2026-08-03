<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Driver self-registration (FR-03). Registration is driver-only:
 * agency administrator accounts are provisioned, never self-registered.
 */
class RegisterRequest extends FormRequest
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
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:strict', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Same per-agency rule as an admin-added driver (FR-03/FR-06): a
            // self-registering driver whose licence is already on file would
            // otherwise be a second row for one person.
            'license_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'license_number')->where('agency_id', $this->input('agency_id')),
            ],
            'license_expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'agency_id.required' => 'Please select your agency.',
            'agency_id.exists' => 'The selected agency does not exist.',
            'email.unique' => 'An account with this email address already exists.',
            'license_number.unique' => 'A driver with this license number is already registered with that agency.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
