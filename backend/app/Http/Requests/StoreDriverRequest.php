<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesVehicleAssignment;
use App\Models\Vehicle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin-added driver (FR-06) — created 'active' immediately, unlike
 * self-registration (FR-03) which starts 'pending'.
 */
class StoreDriverRequest extends FormRequest
{
    use ValidatesVehicleAssignment;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:strict', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // A licence number identifies one person, so a duplicate means the
            // same driver was added twice — which also double-counts one physical
            // licence in the FR-08 expiring figures, from two rows that can hold
            // different expiry dates. Per agency (a driver belongs to one) and
            // nullable, so any number of drivers may have none recorded.
            'license_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'license_number')->where('agency_id', $agencyId),
            ],
            'license_expiry_date' => ['nullable', 'date'],
            // TESDA NC II (Driving) — optional second credential, monitored like
            // the licence (FR-08, FR-10, 2026-09). Nullable, no unique index.
            'nc_ii_number' => ['nullable', 'string', 'max:50'],
            'nc_ii_expiry_date' => ['nullable', 'date'],
            // Any vehicle of the admin's own agency; the slot/conflict check for
            // the chosen role happens in withValidator (2026-09).
            'assigned_vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'assigned_vehicle_role' => ['nullable', Rule::in(['primary', 'secondary'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // A new driver holds no slot yet, so pass null.
        $validator->after(fn (Validator $v) => $this->validateVehicleAssignment($v, null));
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists.',
            'license_number.unique' => 'A driver with this license number already exists in your agency.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
