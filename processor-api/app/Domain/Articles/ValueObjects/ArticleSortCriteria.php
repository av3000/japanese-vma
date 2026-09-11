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
final readonly class ArticleSortCriteria
{
    public const DEFAULT = '-created_at';

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
     * Order by primary key. Not part of the public contract, which is why it is not
     * in SORTABLE; internal callers such as the word detail related-Article panel
     * use it because that panel has always been ordered by id.
     */
    public static function byId(SortDirection $direction = SortDirection::ASC): self
    {
        return new self(ArticleSortField::ID, $direction);
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
