<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * One administrator resets a colleague's password (FR-04a, 2026-08).
 *
 * This is the case that actually closes the lockout hole. An agency may have
 * several administrators — BFP ships with two by design — so a colleague can
 * put a locked-out admin back in without a developer touching the database.
 *
 * Unlike a driver reset, this one demands the ACTING admin's own password
 * first. Resetting a peer hands over an account with the same reach as your
 * own, so an unattended session must not be enough to do it. A driver reset is
 * routine administration; taking over a colleague's administrator account is
 * not, and the two are priced differently on purpose.
 */
class ResetAdminPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! Hash::check((string) $this->input('current_password'), $this->user()->password)) {
                    $validator->errors()->add('current_password', 'That is not your current password.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Confirm your own password to reset a colleague’s.',
            'password.min' => 'The new password must be at least 8 characters.',
        ];
    }
}
