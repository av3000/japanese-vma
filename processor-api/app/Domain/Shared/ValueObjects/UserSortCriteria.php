<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Enums\UserSortField;

final readonly class UserSortCriteria
{
    public function __construct(
        public UserSortField $field,
        public SortDirection $direction
    ) {}

    public static function byCreationDateDesc(): self
    {
        return new self(UserSortField::CREATED_AT, SortDirection::DESC);
    }

    public static function byNameAsc(): self
    {
        return new self(UserSortField::NAME, SortDirection::ASC);
    }

    public static function fromFieldAndDirection(string $field, string $direction): self
    {
        return new self(
            UserSortField::from($field),
            SortDirection::from($direction)
        );
    }
}
