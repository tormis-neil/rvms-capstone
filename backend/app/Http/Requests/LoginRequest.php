<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // email:strict rejects CRLF and other RFC warnings — the mitigation
            // for GHSA-5vg9-5847-vvmq on a framework line past its security
            // window (security audit R10.2).
            'email' => ['required', 'string', 'email:strict'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email address is required to log in.',
            'email.email' => 'The email address must be a valid email address.',
            'password.required' => 'The password is required to log in.',
        ];
    }
}
