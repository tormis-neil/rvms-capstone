<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'The new password must be at least 8 characters.',
        ];
    }
}
