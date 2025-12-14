<?php

declare(strict_types=1);

namespace App\Http\V1\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $configuredGuards = array_keys(config('auth.guards'));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'guard_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($configuredGuards),
            ],
        ];
    }

    public function getName(): string
    {
        return $this->input('name');
    }

    public function getGuardName(): ?string
    {
        return $this->input('guard_name');
    }
}
