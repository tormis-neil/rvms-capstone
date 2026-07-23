<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin damage-report review (FR-12). Marks the report Reviewed and,
 * optionally, changes the vehicle's operational status — only the three
 * manual statuses (Dispatched is written by the Dispatch module alone, FR-18).
 * No admin-remarks column (design decision 7 — deliberately excluded).
 */
class ReviewDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_status' => ['nullable', Rule::in(Vehicle::MANUAL_STATUSES)],
        ];
    }
}
