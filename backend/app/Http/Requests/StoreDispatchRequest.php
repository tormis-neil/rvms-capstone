<?php

namespace App\Http\Requests;

use App\Models\Dispatch;
use App\Services\DispatchGuard;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Open / edit a dispatch (FR-15). The vehicle and driver must belong to the
 * admin's own agency; "Others" mission requires the free-text detail; the
 * time-out odometer is optional (digitizes the paper form's odometer field).
 */
class StoreDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'driver_id' => [
                'required',
                Rule::exists('users', 'id')->where('agency_id', $agencyId)->where('role', 'driver'),
            ],
            'mission_type' => ['required', Rule::in(Dispatch::MISSION_TYPES)],
            'mission_other' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $this->input('mission_type') === Dispatch::MISSION_OTHERS),
            ],
            'location' => ['required', 'string', 'max:255'],
            'time_out' => ['required', 'date'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mission_other.required' => 'Please specify the mission when the type is "Others".',
        ];
    }

    /**
     * A vehicle can only be out on one mission at a time.
     *
     * Without this, two dispatches could be open on the same vehicle at once —
     * easily done when an agency has more than one administrator (BFP does) and
     * both had the page loaded before either dispatched, since each dropdown
     * still showed the vehicle as Operational. Closing one of them would then
     * write a return status while the other was still running, leaving the
     * vehicle reading Operational while it was still in the field and breaking
     * the single shared status (FR-18).
     *
     * `writeFromDispatch` is deliberately exempt from VehicleStatusWriter's
     * active-dispatch guard — Dispatch owns the Dispatched state — so this is
     * the layer that has to catch it.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $guard = app(DispatchGuard::class);

            // Editing an existing dispatch must not collide with itself.
            $editingId = $this->route('dispatch')?->id;

            if ($vehicleId = $this->input('vehicle_id')) {
                if ($clash = $guard->openForVehicle((int) $vehicleId, $editingId)) {
                    $validator->errors()->add('vehicle_id', $guard->vehicleMessage($clash));
                }
            }

            // A driver cannot be in two vehicles at once. Physically impossible,
            // and it would corrupt the dispatch report and per-driver history.
            if ($driverId = $this->input('driver_id')) {
                if ($clash = $guard->openForDriver((int) $driverId, $editingId)) {
                    $validator->errors()->add('driver_id', $guard->driverMessage($clash));
                }
            }
        });
    }
}
