<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Update License modal (FR-06/FR-08) — records the new expiry date after
 * a driver renews; the license status is recomputed from it automatically.
 */
class UpdateDriverLicenseRequest extends FormRequest
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
        return [
            'license_expiry_date' => ['required', 'date'],
        ];
    }
}
