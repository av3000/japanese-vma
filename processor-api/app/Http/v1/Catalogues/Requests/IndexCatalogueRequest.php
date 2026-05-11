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
            'owner_uid' => 'sometimes|uuid',
            'type' => 'sometimes|integer',
            'sort_by' => 'sometimes|string|in:created_at,views',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer',
            'page' => 'sometimes|integer',
            'public_only' => 'sometimes|boolean',
            'custom_only' => 'sometimes|boolean',
            'include_stats_counts' => 'sometimes|boolean',
            'include_hashtags' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Search term must be a string',
            'owner_uid.uuid' => 'Owner UID must be a valid UUID',
            'type.integer' => 'Type must be a number',
            'sort_by.string' => 'Sort field must be a string',
            'sort_dir.string' => 'Sort direction must be a string',
            'sort_by.in' => 'Sort field must be one of: created_at, views',
            'sort_dir.in' => 'Sort direction must be asc or desc',
            'per_page.integer' => 'Per page must be a number',
            'page.integer' => 'Page must be a number',
            'public_only.boolean' => 'Public only must be a boolean value',
            'custom_only.boolean' => 'Custom only must be a boolean value',
            'include_stats_counts.boolean' => 'Include stats must be a boolean value',
            'include_hashtags.boolean' => 'Include hashtags must be a boolean value',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'include_stats_counts',
            'include_hashtags',
            'public_only',
            'custom_only'
        ];

        $normalized = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $normalized[$field] = filter_var(
                    $this->input($field),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
            }
        }

        $this->merge($normalized);
    }
}
