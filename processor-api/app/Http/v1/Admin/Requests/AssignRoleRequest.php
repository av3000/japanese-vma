<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // This FormRequest is typically used under an 'admin' middleware route.
        // The middleware or a dedicated policy should handle the actual authorization logic
        // (e.g., checking if the authenticated user has permission to assign roles).
        // If this FormRequest itself needs to perform an authorization check, you'd
        // inject an authorization service here or use Laravel's Gate/Policy facade.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get the role name from the request body.
     */
    public function getRoleName(): string
    {
        return $this->input('role');
    }
}
