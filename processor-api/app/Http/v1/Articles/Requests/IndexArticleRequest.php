<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexArticleRequest extends FormRequest
{
    /**
     * Shortest accepted search string. SearchTerm enforces the same floor but throws
     * a plain InvalidArgumentException, which the exception handler does not map, so
     * a one-character q would surface as a 500. Bound it at the HTTP edge instead.
     */
    public const MIN_SEARCH_LENGTH = 2;

    /** Longest accepted search string. */
    public const MAX_SEARCH_LENGTH = 200;

    /** Cap on unique values in any one multi-value filter. */
    public const MAX_FILTER_VALUES = 20;

    /**
     * Offset ceiling. Deep offsets make PostgreSQL scan and discard everything
     * before the page; AFM-04 may lower this on query-plan evidence but may not
     * raise it without review.
     */
    public const MAX_OFFSET = 10000;

    /**
     * Temporary aliases kept so callers can migrate without an atomic
     * backend/frontend deploy. AFM-07 removes them.
     */
    private const LEGACY_KEYS = ['search', 'category', 'sort_by', 'sort_dir'];

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
            'q' => 'sometimes|string|min:'.self::MIN_SEARCH_LENGTH.'|max:'.self::MAX_SEARCH_LENGTH,
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
            'sort' => ['sometimes', Rule::in(ArticleListSort::allowedValues())],
            'per_page' => 'sometimes|integer|min:1|max:'.Pagination::MAX_PER_PAGE,
            'page' => 'sometimes|integer|min:'.Pagination::MIN_PAGE,
            'include_stats_counts' => 'sometimes|boolean',
            'include_hashtags' => 'sometimes|boolean',
            'include_kanjis' => 'sometimes|boolean',
            'include_words' => 'sometimes|boolean',
            'include_facets' => 'sometimes|boolean',

            // Compatibility aliases, normalized in prepareForValidation().
            'search' => 'sometimes|string|min:'.self::MIN_SEARCH_LENGTH.'|max:'.self::MAX_SEARCH_LENGTH,
            'category' => 'sometimes|integer|between:1,6',
            // Validated against the same closed set as `sort`, so a bad legacy value
            // still reports against the key the caller actually sent.
            'sort_by' => ['sometimes', Rule::in(ArticleListSort::allowedFieldNames())],
            'sort_dir' => ['sometimes', Rule::in(ArticleListSort::LEGACY_DIRECTIONS)],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'Search may not be longer than '.self::MAX_SEARCH_LENGTH.' characters',
            'jlpt_levels.*.in' => 'JLPT level must be one of: '.implode(', ', ArticleJlptLevel::values()),
            'jlpt_levels.max' => 'At most '.self::MAX_FILTER_VALUES.' JLPT levels may be supplied',
            'hashtag_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' hashtags may be supplied',
            'kanji_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' kanjis may be supplied',
            'word_ids.max' => 'At most '.self::MAX_FILTER_VALUES.' words may be supplied',
            'author_uid.uuid' => 'Author UID must be a valid UUID',
            'created_from.date_format' => 'created_from must be a date in YYYY-MM-DD format',
            'created_to.date_format' => 'created_to must be a date in YYYY-MM-DD format',
            'sort.in' => 'Sort must be one of: '.implode(', ', ArticleListSort::allowedValues()),
            'category.between' => 'Category must be between 1 and 6',
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
            $this->rejectConflictingAliases($validator);
            $this->rejectUnreachableOffset($validator);
        });
    }

    /**
     * Canonical values only. Aliases are resolved here so nothing downstream has to
     * know they ever existed.
     *
     * @return array<string, mixed>
     */
    public function canonical(): array
    {
        $validated = $this->validated();

        $canonical = $validated;

        foreach (self::LEGACY_KEYS as $legacy) {
            unset($canonical[$legacy]);
        }

        if (! isset($canonical['q']) && isset($validated['search'])) {
            $canonical['q'] = $validated['search'];
        }

        if (! isset($canonical['jlpt_levels']) && isset($validated['category'])) {
            $level = ArticleJlptLevel::fromLegacyCategory((int) $validated['category']);

            if ($level !== null) {
                $canonical['jlpt_levels'] = [$level->value];
            }
        }

        if (! isset($canonical['sort']) && (isset($validated['sort_by']) || isset($validated['sort_dir']))) {
            $canonical['sort'] = ArticleListSort::fromLegacy(
                $validated['sort_by'] ?? null,
                $validated['sort_dir'] ?? null,
            )->toSigned();
        }

        return $canonical;
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
     * Sending both a canonical key and its alias is ambiguous, and silently
     * preferring one would make the migration impossible to verify.
     */
    private function rejectConflictingAliases(Validator $validator): void
    {
        if ($this->has('q') && $this->has('search')) {
            $validator->errors()->add('search', 'Use either q or the legacy search parameter, not both.');
        }

        if ($this->has('jlpt_levels') && $this->has('category')) {
            $validator->errors()->add('category', 'Use either jlpt_levels or the legacy category parameter, not both.');
        }

        if ($this->has('sort') && ($this->has('sort_by') || $this->has('sort_dir'))) {
            $validator->errors()->add('sort', 'Use either sort or the legacy sort_by/sort_dir parameters, not both.');
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
