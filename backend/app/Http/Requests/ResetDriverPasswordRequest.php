<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An administrator sets a new password for one of their drivers (FR-22, 2026-08).
 *
 * The routine half of "I forgot my password". A driver in the field has no way
 * to reset their own — the system sends no email, deliberately, because SMTP
 * credentials are one more thing to configure at turnover and one more thing to
 * fail silently. So the login screen tells them to contact their administrator,
 * and this is the action that makes that advice true.
 *
 * No `confirmed` rule: the admin is typing a password FOR someone else and then
 * reading it out to them, so a second box confirms nothing — they can see what
 * they typed. `confirmed` belongs on self-service changes, where a typo would
 * lock the person out of their own account.
 */
class ResetDriverPasswordRequest extends FormRequest
{
    /**
     * Settle whether the caller may see this driver at all, BEFORE any rule runs.
     *
     * A form request validates first and reaches the controller second, so with
     * the check left in the controller a cross-agency id was answered with the
     * 422 for a missing `current_password` instead of the 404 that FR-02
     * requires. The invariant `AgencyIsolationSweepTest` guards is that
     * validation never runs against a record the caller cannot see — worth
     * keeping literal rather than arguing case by case about which messages
     * happen to be harmless.
     */
    public function authorize(): bool
    {
        $driver = $this->route('driver');

        if (! $driver instanceof User
            || $driver->role !== User::ROLE_DRIVER
            || $driver->agency_id !== $this->user()?->agency_id) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            // The acting administrator confirms who they are before taking over
            // someone else's sign-in. Without it, an unattended logged-in
            // dashboard is enough to seize a driver's account — and the driver
            // only finds out from the notification afterwards (FR-22, 2026-08).
            //
            // Checked against the REQUEST's user rather than with
            // `current_password:web`: the dashboard authenticates on the web
            // session guard and the mobile API on Sanctum, so naming one guard
            // would make the rule pass on that platform and fail on the other.
            'current_password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Hash::check((string) $value, (string) $this->user()->password)) {
                        $fail('That is not your current password.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Confirm your own password before resetting a driver’s.',
            'password.min' => 'The new password must be at least 8 characters.',
        ];
    }
}
