<?php

namespace App\Http\Requests;

use App\Models\Dispatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Close a dispatch (FR-16). Records the time in and the return status (one of
 * the three manual statuses). The time-in odometer is optional and, when
 * present and higher than the vehicle's current mileage, updates it
 * (mileage-on-arrival → feeds mileage-based PM, FR-14).
 */
class CloseDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_in' => ['required', 'date'],
            'return_status' => ['required', Rule::in(Dispatch::RETURN_STATUSES)],
            'odometer_in' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
