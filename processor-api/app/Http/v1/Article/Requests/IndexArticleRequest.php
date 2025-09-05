<?php

namespace App\Http\v1\Article\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

   public function rules(): array
    {
        return [
            'category' => 'sometimes|integer',
            'search' => 'sometimes|string',
            'sort_by' => 'sometimes|string',
            'sort_dir' => 'sometimes|string',
            'per_page' => 'sometimes|integer',
            'include_stats' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Search term must be a string',
            'sort_by.string' => 'Sort field must be a string',
            'sort_dir.string' => 'Sort direction must be a string',
            'per_page.integer' => 'Per page must be a number',
            'include_stats.boolean' => 'Include stats must be a boolean value',
        ];
    }

    /**
     * Get custom attributes for validator errors (helpful for API documentation)
     */
    public function attributes(): array
    {
        return [
            'include_stats' => 'include statistics',
        ];
    }
}
