<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update License modal (FR-06/FR-08) — records the new expiry date after
 * a driver renews; the license status is recomputed from it automatically.
 */
class UpdateDriverLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_expiry_date' => ['required', 'date'],
        ];
    }
}
