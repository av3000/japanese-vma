<?php

namespace App\Http\v1\Catalogues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|integer|in:5,6,7,8,9',
            'publicity' => 'nullable|boolean',
            'tags' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A list title is required',
            'title.min' => 'The list title must be at least 2 characters',
            'title.max' => 'The list title must not exceed 255 characters',
            'type.required' => 'A list type is required',
            'type.in' => 'List type must be one of 5, 6, 7, 8, or 9 for custom lists',
        ];
    }
}
