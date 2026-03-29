<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\ValueObjects;

use App\Domain\Catalogues\Enums\CatalogueSortField;
use App\Domain\Shared\Enums\SortDirection;

final readonly class CatalogueSortCriteria
{
    public function __construct(
        public CatalogueSortField $field,
        public SortDirection $direction
    ) {}

    public static function default(): self
    {
        return new self(CatalogueSortField::CREATED_AT, SortDirection::DESC);
    }

    public static function fromInputOrDefault(?string $field, ?string $direction): self
    {
        $fieldEnum = CatalogueSortField::tryFrom($field ?? '') ?? CatalogueSortField::CREATED_AT;
        $dirEnum = SortDirection::tryFrom(strtolower($direction ?? '')) ?? SortDirection::DESC;

        return new self($fieldEnum, $dirEnum);
    }
}
