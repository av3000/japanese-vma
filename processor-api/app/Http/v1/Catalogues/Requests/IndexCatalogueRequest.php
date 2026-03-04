<?php

namespace App\Http\v1\Catalogues\Requests;


use Illuminate\Foundation\Http\FormRequest;

class IndexCatalogueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string',
            'sort_by' => 'sometimes|string',
            'sort_dir' => 'sometimes|string',
            'per_page' => 'sometimes|integer',
            'page' => 'sometimes|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Search term must be a string',
            'sort_by.string' => 'Sort field must be a string',
            'sort_dir.string' => 'Sort direction must be a string',
            'per_page.integer' => 'Per page must be a number',
            'page.integer' => 'Page must be a number',
        ];
    }
}
