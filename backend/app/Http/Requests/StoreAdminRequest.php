<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * An administrator creates another administrator for their OWN agency (2026-09,
 * design decision 6 revised, project-lead approved).
 *
 * Administrator accounts stay "provisioned within the system" (Ch1): the public
 * registration endpoint remains driver-only, and the new account is forced into
 * the acting administrator's agency — the form carries no agency field, so an
 * admin can never mint one for another agency. The acting administrator confirms
 * their OWN password first, the same safeguard the driver password reset uses:
 * an admin account can see every record in the agency, so creating one must not
 * be possible from a dashboard someone walked away from.
 *
 * The server command `rvms:create-admin` remains the fallback for when nobody
 * can sign in at all.
 */
class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route is already behind auth + role:admin; a driver never reaches here.
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        // Fields are prefixed admin_* so they never collide with the profile
        // form on the same page, which owns plain name/email/password — a shared
        // key would cross-populate old() input and @error state between the two.
        return [
            'admin_name' => ['required', 'string', 'max:255'],
            // Email is the login identifier — unique across the whole system,
            // not per agency, so two people can never share a sign-in.
            'admin_email' => ['required', 'string', 'email:strict', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Confirm the acting administrator's own password before a peer
            // account with equal reach is created.
            if ($validator->errors()->has('current_password')) {
                return;
            }

            if (! Hash::check((string) $this->input('current_password'), (string) $this->user()->password)) {
                $validator->errors()->add('current_password', 'That is not your current password.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'admin_email.unique' => 'An account with this email address already exists.',
            'admin_password.confirmed' => 'The password confirmation does not match.',
            'current_password.required' => 'Confirm your own password before creating an administrator.',
        ];
    }
}
