<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

use App\Domain\Shared\Enums\ArticleSortField;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;

/**
 * One signed sort key, e.g. `-created_at`.
 *
 * A leading '-' means descending. The accepted set is closed: anything outside it
 * is a 422, never a 500 and never a raw column name reaching SQL.
 *
 * Every sort is followed by `articles.id` in the same direction. Without that
 * tie-breaker, rows sharing a created_at can swap between pages and a user
 * paging through the list sees duplicates and gaps.
 */
final readonly class ArticleListSort
{
    public const DEFAULT = '-created_at';

    /**
     * Direction spellings the legacy sort_dir accepted. Retired by AFM-07.
     */
    public const LEGACY_DIRECTIONS = ['asc', 'desc', 'ascending', 'descending'];

    /**
     * Sortable fields, deliberately narrower than ArticleSortField: bare `id` is an
     * implementation detail of the tie-breaker, not a product-facing sort.
     */
    private const SORTABLE = [
        'created_at' => ArticleSortField::CREATED_AT,
        'updated_at' => ArticleSortField::UPDATED_AT,
        'title_jp' => ArticleSortField::TITLE_JP,
        'title_en' => ArticleSortField::TITLE_EN,
    ];

    private function __construct(
        public ArticleSortField $field,
        public SortDirection $direction,
    ) {
    }

    public static function default(): self
    {
        return self::fromSigned(self::DEFAULT);
    }

    public static function fromSignedOrDefault(?string $signed): self
    {
        return $signed === null || $signed === ''
            ? self::default()
            : self::fromSigned($signed);
    }

    public static function fromSigned(string $signed): self
    {
        $descending = str_starts_with($signed, '-');
        $name = $descending ? substr($signed, 1) : $signed;

        $field = self::SORTABLE[$name] ?? null;

        if ($field === null) {
            throw ValueObjectValidationException::forField(
                'sort',
                "Invalid sort value: {$signed}",
            );
        }

        return new self($field, $descending ? SortDirection::DESC : SortDirection::ASC);
    }

    /**
     * Translate the legacy sort_by/sort_dir pair into the canonical signed key.
     * Retired by AFM-07.
     *
     * Unlike the public contract this also accepts bare `id`, because internal
     * callers (WordDetailService orders its related-Article panel by id) predate the
     * canonical set. Public `sort_by` input is validated against allowedFieldNames()
     * before it gets here, so `id` is not reachable over HTTP.
     */
    public static function fromLegacy(?string $sortBy, ?string $sortDir): self
    {
        $name = $sortBy ?? 'created_at';
        $descending = strtolower(trim((string) ($sortDir ?? 'desc'))) !== 'asc';

        if ($name === 'id') {
            return new self(
                ArticleSortField::ID,
                $descending ? SortDirection::DESC : SortDirection::ASC,
            );
        }

        return self::fromSigned(($descending ? '-' : '').$name);
    }

    public function isDescending(): bool
    {
        return $this->direction === SortDirection::DESC;
    }

    public function toSigned(): string
    {
        return ($this->isDescending() ? '-' : '').$this->field->value;
    }

    /**
     * The unsigned field names, for validating the legacy sort_by alias.
     *
     * @return array<int, string>
     */
    public static function allowedFieldNames(): array
    {
        return array_keys(self::SORTABLE);
    }

    /**
     * Every signed value the public contract accepts, for validation and OpenAPI.
     *
     * @return array<int, string>
     */
    public static function allowedValues(): array
    {
        $values = [];

        foreach (array_keys(self::SORTABLE) as $name) {
            $values[] = $name;
            $values[] = '-'.$name;
        }

        return $values;
    }
}
