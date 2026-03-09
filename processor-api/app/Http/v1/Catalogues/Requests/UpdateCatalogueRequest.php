<?php

namespace App\Http\v1\Catalogues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:2|max:255',
            'type' => 'sometimes|integer|in:5,6,7,8,9',
            'publicity' => 'sometimes|boolean',
            'tags' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'The list title must be at least 2 characters',
            'title.max' => 'The list title must not exceed 255 characters',
            'type.in' => 'List type must be one of 5, 6, 7, 8, or 9 for custom lists',
        ];
    }

    public function hasAnyUpdateableFields(): bool
    {
        return collect([
            'title',
            'type',
            'publicity',
            'tags',
        ])->some(fn (string $field) => $this->exists($field));
    }
}
