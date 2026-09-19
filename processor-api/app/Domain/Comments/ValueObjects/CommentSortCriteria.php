<?php

declare(strict_types=1);

namespace App\Domain\Comments\ValueObjects;

use App\Domain\Comments\Enums\CommentSortField;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;

/**
 * Thread sort, expressed as two wire parameters rather than one signed string.
 *
 * ArticleSortCriteria takes `-created_at`; the Comment contract has always taken
 * `sort_by` and `sort_dir` separately and clients depend on that, so the two
 * shapes differ on purpose. What they share is the part that matters: an invalid
 * field cannot be represented, so no caller downstream re-checks it against a
 * column whitelist before reaching `orderBy`.
 */
final readonly class CommentSortCriteria
{
    /**
     * Sortable fields. Narrower than the table: `id` is the tie-breaker's
     * business, not a product-facing sort.
     */
    private const SORTABLE = [
        'created_at' => CommentSortField::CREATED_AT,
        'updated_at' => CommentSortField::UPDATED_AT,
    ];

    private function __construct(
        public CommentSortField $field,
        public SortDirection $direction,
    ) {
    }

    /**
     * Newest conversations first - what a thread view opens on.
     */
    public static function default(): self
    {
        return new self(CommentSortField::CREATED_AT, SortDirection::DESC);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return self::from(
            isset($validated['sort_by']) ? (string) $validated['sort_by'] : null,
            isset($validated['sort_dir']) ? (string) $validated['sort_dir'] : null,
        );
    }

    public static function from(?string $field, ?string $direction): self
    {
        return new self(
            self::resolveField($field),
            self::resolveDirection($direction),
        );
    }

    /**
     * Every value the public contract accepts, for request validation and OpenAPI.
     *
     * @return array<int, string>
     */
    public static function allowedFields(): array
    {
        return array_keys(self::SORTABLE);
    }

    public function isDescending(): bool
    {
        return $this->direction === SortDirection::DESC;
    }

    private static function resolveField(?string $field): CommentSortField
    {
        if ($field === null || $field === '') {
            return CommentSortField::CREATED_AT;
        }

        $resolved = self::SORTABLE[$field] ?? null;

        if ($resolved === null) {
            throw ValueObjectValidationException::forField(
                'sort_by',
                "Invalid sort field: {$field}",
            );
        }

        return $resolved;
    }

    private static function resolveDirection(?string $direction): SortDirection
    {
        if ($direction === null || $direction === '') {
            return SortDirection::DESC;
        }

        $resolved = SortDirection::tryFrom(strtolower($direction));

        if ($resolved === null) {
            throw ValueObjectValidationException::forField(
                'sort_dir',
                "Invalid sort direction: {$direction}",
            );
        }

        return $resolved;
    }
}
