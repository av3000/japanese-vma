<?php
namespace App\Domain\Articles\ValueObjects;

use App\Domain\Shared\Enums\ArticleSortField;
use App\Domain\Shared\Enums\SortDirection;
use InvalidArgumentException;

readonly class ArticleSortCriteria
{
    public function __construct(
        public ArticleSortField $field,
        public SortDirection $direction
    ) {}

    public static function fromInputOrDefault(?string $field, ?string $direction): self
    {
        return new self(
            self::parseField($field),
            self::parseDirection($direction)
        );
    }

    public static function default(): self
    {
        return new self(
            ArticleSortField::CREATED_AT,
            SortDirection::DESC
        );
    }

    private static function parseField(?string $field): ArticleSortField
    {
        if ($field === null) {
            return ArticleSortField::CREATED_AT;
        }

        return ArticleSortField::tryFrom($field)
            ?? throw new InvalidArgumentException("Invalid sort field: {$field}");
    }

    private static function parseDirection(?string $direction): SortDirection
    {
        if ($direction === null) {
            return SortDirection::DESC;
        }

        // Business rule: handle common variations
        $normalized = strtolower(trim($direction));
        return match($normalized) {
            'asc', 'ascending' => SortDirection::ASC,
            'desc', 'descending' => SortDirection::DESC,
            default => throw new InvalidArgumentException("Invalid sort direction: {$direction}")
        };
    }

    public function isAscending(): bool
    {
        return $this->direction === SortDirection::ASC;
    }
}
