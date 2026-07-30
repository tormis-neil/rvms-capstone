<?php

namespace App\Http\Requests;

use App\Services\ReportBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for a generated report (FR-20).
 *
 * The date range is one of the prototype's three presets, never a free date:
 * anything else is refused with 422 rather than silently falling back to "All
 * Dates", which would hand the admin a printout wider than the one they asked
 * for without saying so.
 *
 * The vehicle and driver ids are checked against the CALLER'S OWN agency, so a
 * guessed id from another agency fails validation instead of quietly returning
 * an empty report — the same FR-02 boundary every other module enforces.
 */
class ReportRequest extends FormRequest
{
    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'range' => ['nullable', Rule::in(array_keys(ReportBuilder::RANGES))],
            'vehicle_id' => [
                'nullable', 'integer',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'driver_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('agency_id', $agencyId)->where('role', 'driver'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'range.in' => 'Choose one of the available date ranges.',
            'vehicle_id.exists' => 'That vehicle is not in your agency.',
            'driver_id.exists' => 'That driver is not in your agency.',
        ];
    }

    /**
     * Filters in the shape ReportBuilder expects, with the labels the printout
     * prints in its filter summary line.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'range' => $this->input('range', 'all'),
            'vehicle_id' => $this->integer('vehicle_id') ?: null,
            'driver_id' => $this->integer('driver_id') ?: null,
        ];
    }
}
