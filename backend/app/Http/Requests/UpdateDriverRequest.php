<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Admin edit of a driver record (FR-06). Editing NEVER changes a password:
 * that goes only through resetPassword() (FR-22), which re-authenticates the
 * admin and notifies the driver. The vehicle select defaults to "no change"
 * and can only ever pick a vehicle that is unassigned or already this driver's
 * own — it can never steal a vehicle belonging to another driver.
 */
class UpdateDriverRequest extends FormRequest
{
    /**
     * The ownership check, BEFORE validation (security audit R10.2/R10.3).
     *
     * FormRequest validation runs ahead of any code in the controller, so a
     * foreign-but-existing driver id used to answer 422 (validation messages)
     * while a nonexistent id answered 404 — and that difference let another
     * agency enumerate which user ids exist. The guard therefore lives here,
     * and throws 404 rather than returning false: false would produce a 403,
     * which is the same oracle with a different code.
     */
    public function authorize(): bool
    {
        $driver = $this->route('driver');

        if ($driver instanceof User
            && ($driver->role !== User::ROLE_DRIVER || $driver->agency_id !== $this->user()->agency_id)) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;
        $driverId = $this->route('driver')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:strict', 'max:255', Rule::unique('users', 'email')->ignore($driverId)],
            // No password rule: Edit Driver cannot change a password (design decision,
            // 2026-08). Password changes go only through resetPassword() (FR-22), which
            // re-authenticates the admin and notifies the driver — safeguards this path
            // silently lacked. A `password` field in the request is simply ignored.
            // Per agency and nullable; ignores this driver so an edit that leaves
            // the licence untouched does not collide with itself.
            'license_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'license_number')
                    ->where('agency_id', $agencyId)
                    ->ignore($driverId),
            ],
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
            'license_number.unique' => 'A driver with this license number already exists in your agency.',
            'assigned_vehicle_id.exists' => 'That vehicle is not available to assign (it may already have a different driver).',
        ];
    }
}
