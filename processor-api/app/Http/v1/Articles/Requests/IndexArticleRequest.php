<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexArticleRequest extends FormRequest
{
    /** Cap on unique values in any one multi-value filter. */
    public const MAX_FILTER_VALUES = 20;

    /**
     * Offset ceiling. Deep offsets make PostgreSQL scan and discard everything
     * before the page; AFM-04 may lower this on query-plan evidence but may not
     * raise it without review.
     */
    public const MAX_OFFSET = 10000;

    private const BOOLEAN_FIELDS = [
        'include_stats_counts',
        'include_hashtags',
        'include_kanjis',
        'include_words',
        'include_facets',
    ];

    private const ARRAY_FILTERS = ['jlpt_levels', 'hashtag_ids', 'kanji_ids', 'word_ids'];

    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            // Bounds are SearchTerm's, so the request and the value object cannot drift.
            'q' => 'sometimes|string|min:'.SearchTerm::MIN_LENGTH.'|max:'.SearchTerm::MAX_LENGTH,
            'jlpt_levels' => 'sometimes|array|max:'.self::MAX_FILTER_VALUES,
            'jlpt_levels.*' => [Rule::in(ArticleJlptLevel::values())],
            'hashtag_ids' => 'sometimes|array|max:'.self::MAX_FILTER_VALUES,
            'hashtag_ids.*' => 'integer|min:1',
            'author_uid' => 'sometimes|uuid',
            'kanji_ids' => 'sometimes|array|max:'.self::MAX_FILTER_VALUES,
            'kanji_ids.*' => 'integer|min:1',
            'word_ids' => 'sometimes|array|max:'.self::MAX_FILTER_VALUES,
            'word_ids.*' => 'integer|min:1',
            'created_from' => 'sometimes|date_format:Y-m-d',
            'created_to' => 'sometimes|date_format:Y-m-d',
            'sort' => ['sometimes', Rule::in(ArticleSortCriteria::allowedValues())],
            'per_page' => 'sometimes|integer|min:1|max:'.Pagination::MAX_PER_PAGE,
            'page' => 'sometimes|integer|min:'.Pagination::MIN_PAGE,
            'include_stats_counts' => 'sometimes|boolean',
            'include_hashtags' => 'sometimes|boolean',
            'include_kanjis' => 'sometimes|boolean',
            'include_words' => 'sometimes|boolean',
            'include_facets' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'Search may not be longer than '.SearchTerm::MAX_LENGTH.' characters',
            'jlpt_levels.*.in' => 'JLPT level must be one of: '.implode(', ', ArticleJlptLevel::values()),
            'jlpt_levels.max' => 'At most '.self::MAX_FILTER_VALUES.' JLPT levels may be supplied',
            'hashtag_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' hashtags may be supplied',
            'kanji_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' kanjis may be supplied',
            'word_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' words may be supplied',
            'author_uid.uuid' => 'Author UID must be a valid UUID',
            'created_from.date_format' => 'created_from must be a date in YYYY-MM-DD format',
            'created_to.date_format' => 'created_to must be a date in YYYY-MM-DD format',
            'sort.in' => 'Sort must be one of: '.implode(', ', ArticleSortCriteria::allowedValues()),
            'per_page.max' => 'Per page may not be greater than '.Pagination::MAX_PER_PAGE,
        ];
    }

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

        // Duplicate filter values are user noise, not intent. Collapsing them here
        // keeps the cardinality cap honest and stops the frontend cache key from
        // splitting on value order.
        foreach (self::ARRAY_FILTERS as $filter) {
            if (is_array($this->input($filter))) {
                $normalized[$filter] = array_values(array_unique($this->input($filter), SORT_REGULAR));
            }
        }

        $this->merge($normalized);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectUnknownKeys($validator);
            $this->rejectUnreachableOffset($validator);
            $this->rejectReversedDateRange($validator);
        });
    }

    private function rejectUnknownKeys(Validator $validator): void
    {
        $supported = [];

        foreach (array_keys($this->rules()) as $rule) {
            $supported[] = explode('.', $rule)[0];
        }

        foreach (array_keys($this->all()) as $key) {
            if (! in_array($key, $supported, true)) {
                $validator->errors()->add($key, "The {$key} parameter is not supported by this endpoint.");
            }
        }
    }

    /**
     * Checked here rather than left to ArticleDateRange so the request is the single
     * input gate: everything validated() returns can be turned into criteria without
     * a value object throwing.
     */
    private function rejectReversedDateRange(Validator $validator): void
    {
        $from = $this->input('created_from');
        $to = $this->input('created_to');

        if (! is_string($from) || ! is_string($to) || $from === '' || $to === '') {
            return;
        }

        if ($validator->errors()->hasAny(['created_from', 'created_to'])) {
            return; // Already reported by the format rules.
        }

        // Both values passed date_format:Y-m-d, so string order is date order.
        if ($from > $to) {
            $validator->errors()->add('created_from', 'created_from must not be later than created_to.');
        }
    }

    private function rejectUnreachableOffset(Validator $validator): void
    {
        $page = (int) ($this->input('page') ?? Pagination::MIN_PAGE);
        $perPage = (int) ($this->input('per_page') ?? Pagination::DEFAULT_PER_PAGE);

        if ($page < 1 || $perPage < 1) {
            return; // Already reported by the bounds rules.
        }

        if (($page - 1) * $perPage > self::MAX_OFFSET) {
            $validator->errors()->add(
                'page',
                'Requested page is beyond the maximum supported offset of '.self::MAX_OFFSET.'.',
            );
        }
    }
}
