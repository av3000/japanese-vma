<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Shared\Enums\ArticleSortField;
use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexArticleRequest extends FormRequest
{
    /**
     * Direction spellings accepted by ArticleSortCriteria. Kept as a temporary
     * compatibility surface; AFM-03 replaces sort_by/sort_dir with a signed `sort`.
     */
    private const SORT_DIRECTIONS = ['asc', 'desc', 'ascending', 'descending'];

    /**
     * Query keys accepted today. `category`, `search`, `sort_by`, and `sort_dir` are
     * temporary compatibility keys: AFM-03 normalizes them, AFM-07 removes them.
     */
    private const BOOLEAN_FIELDS = [
        'include_stats_counts',
        'include_hashtags',
        'include_kanjis',
        'include_words',
    ];

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
            'sort_by' => ['sometimes', Rule::in(array_column(ArticleSortField::cases(), 'value'))],
            'sort_dir' => ['sometimes', Rule::in(self::SORT_DIRECTIONS)],
            'per_page' => 'sometimes|integer|min:1|max:'.Pagination::MAX_PER_PAGE,
            'page' => 'sometimes|integer|min:'.Pagination::MIN_PAGE,
            'include_stats_counts' => 'sometimes|boolean',
            'include_hashtags' => 'sometimes|boolean',
            'include_kanjis' => 'sometimes|boolean',
            'include_words' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category.integer' => 'Category must be a number',
            'search.string' => 'Search term must be a string',
            'author_uid.uuid' => 'Author UID must be a valid UUID',
            'sort_by.in' => 'Sort field must be one of: '.implode(', ', array_column(ArticleSortField::cases(), 'value')),
            'sort_dir.in' => 'Sort direction must be one of: '.implode(', ', self::SORT_DIRECTIONS),
            'per_page.integer' => 'Per page must be a number',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page may not be greater than '.Pagination::MAX_PER_PAGE,
            'page.integer' => 'Page must be a number',
            'page.min' => 'Page must be at least '.Pagination::MIN_PAGE,
            'include_stats_counts.boolean' => 'Include stats must be a boolean value',
            'include_hashtags.boolean' => 'Include hashtags must be a boolean value',
            'include_kanjis.boolean' => 'Include kanjis must be a boolean value',
            'include_words.boolean' => 'Include words must be a boolean value',
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
        $normalized = [];

        foreach (self::BOOLEAN_FIELDS as $field) {
            if ($this->has($field)) {
                $normalized[$field] = filter_var(
                    $this->input($field),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
            }
        }

        // ArticleSortCriteria lower-cases and trims the direction; mirror that here so the
        // allow-list below rejects only genuinely unsupported values.
        if ($this->has('sort_dir') && is_string($this->input('sort_dir'))) {
            $normalized['sort_dir'] = strtolower(trim($this->input('sort_dir')));
        }

        $this->merge($normalized);
    }

    /**
     * Reject query keys this endpoint does not support.
     *
     * This tightens an existing public contract: parameters that were silently ignored
     * now produce a field-level 422. It is what makes the AFM-03 alias rules enforceable.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $supported = array_keys($this->rules());

            foreach (array_keys($this->all()) as $key) {
                if (! in_array($key, $supported, true)) {
                    $validator->errors()->add($key, "The {$key} parameter is not supported by this endpoint.");
                }
            }
        });
    }
}
