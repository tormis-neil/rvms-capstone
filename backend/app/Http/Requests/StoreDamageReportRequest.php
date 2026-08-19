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
            // An explicit list rather than 'image', for two reasons.
            //
            // 'image' admits SVG, and an SVG is a document that can carry
            // scripts — served back from /storage it would execute in the
            // dashboard's own origin when the admin clicks View (stored XSS,
            // security audit R10.2).
            //
            // 'image' also REFUSES heic/heif, which is the default camera
            // format on current Samsung and iPhone handsets. Drivers were
            // picking a photo that looked fine, uploading it, and being
            // refused — while an older jpg from the same gallery worked
            // (2026-08, lead-reported). Listing the formats explicitly keeps
            // SVG out and lets a modern camera photo in.
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:5120'], // optional, ≤5 MB
        ];
    }

    public function messages(): array
    {
        return [
            'nature_of_damage.required' => 'Please describe the nature of the damage.',
            'photo.mimes' => 'The photo must be a JPG, PNG, WEBP or HEIC image.',
            'photo.max' => 'The photo must not be larger than 5 MB. Try a smaller one, '
                .'or reduce the camera resolution.',
            'photo.uploaded' => 'The photo did not finish uploading. It may be larger than '
                .'this server accepts — try a smaller photo.',
        ];
    }
}
