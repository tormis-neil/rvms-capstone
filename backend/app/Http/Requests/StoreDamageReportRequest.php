<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Driver damage-report submission (FR-11). Nature of damage is required; the
 * photo is optional; the vehicle must belong to the driver's own agency. The
 * date reported is auto-set on the server (not accepted from the client).
 */
class StoreDamageReportRequest extends FormRequest
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
            'nature_of_damage' => ['required', 'string'],
            'suspected_parts' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'], // optional, ≤5 MB
        ];
    }

    public function messages(): array
    {
        return [
            'nature_of_damage.required' => 'Please describe the nature of the damage.',
            'photo.image' => 'The attachment must be an image file.',
            'photo.max' => 'The photo must not be larger than 5 MB.',
        ];
    }
}
