<?php

namespace App\Http\v1\Articles\Requests;


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
            'author_uid' => 'sometimes|uuid',
            'sort_by' => 'sometimes|string',
            'sort_dir' => 'sometimes|string',
            'per_page' => 'sometimes|integer',
            'page' => 'sometimes|integer',
            'include_stats_counts' => 'sometimes|boolean',
            'include_kanjis' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category.integer' => 'Category must be a number',
            'search.string' => 'Search term must be a string',
            'author_uid.uuid' => 'Author UID must be a valid UUID',
            'sort_by.string' => 'Sort field must be a string',
            'sort_dir.string' => 'Sort direction must be a string',
            'per_page.integer' => 'Per page must be a number',
            'page.integer' => 'Page must be a number',
            'include_stats_counts.boolean' => 'Include stats must be a boolean value',
            'include_kanjis.boolean' => 'Include kanjis must be a boolean value',
        ];
    }

    /**
     * Get custom attributes for validator errors (helpful for API documentation)
     */
    public function attributes(): array
    {
        return [
            'author_uid' => 'author UID',
            'include_stats_counts' => 'include statistics',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'include_stats_counts',
            'include_kanjis',
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
