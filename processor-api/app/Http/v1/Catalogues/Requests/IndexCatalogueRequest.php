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
            'sort_by' => 'sometimes|string|in:created_at,views',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer',
            'page' => 'sometimes|integer',
            'include_stats_counts' => 'sometimes|boolean',
            'include_hashtags' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Search term must be a string',
            'sort_by.string' => 'Sort field must be a string',
            'sort_dir.string' => 'Sort direction must be a string',
            'sort_by.in' => 'Sort field must be one of: created_at, views',
            'sort_dir.in' => 'Sort direction must be asc or desc',
            'per_page.integer' => 'Per page must be a number',
            'page.integer' => 'Page must be a number',
            'include_stats_counts.boolean' => 'Include stats must be a boolean value',
            'include_hashtags.boolean' => 'Include hashtags must be a boolean value',
        ];
    }
}
