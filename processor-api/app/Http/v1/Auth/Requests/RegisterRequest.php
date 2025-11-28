<?php

declare(strict_types=1);

namespace App\Http\v1\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:users,name',
                'regex:/^[a-zA-Z0-9_-]+$/', // Alphanumeric, underscore, hyphen only
            ],
            'email' => [
                'required',
                'email:rfc,dns', // Strict email validation
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // Checks against leaked password database
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Username can only contain letters, numbers, underscores, and hyphens.',
            'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
        ];
    }
}
