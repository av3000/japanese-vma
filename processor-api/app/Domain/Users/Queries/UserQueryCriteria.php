<?php

declare(strict_types=1);

namespace App\Domain\Users\Queries;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Users\ValueObjects\UserSortCriteria;

// TODO: consider using different one for admin and user for more readability instead of reusing single class
final readonly class UserQueryCriteria
{
    public function __construct(
        public readonly ?EntityId $uuid = null,
        public readonly ?Email $email = null,
        public readonly ?UserName $name = null,
        public readonly ?string $role = null,
        public readonly bool $includeInactive = false,   // Admin-specific
        public readonly bool $publicOnly = false,        // Public-specific (e.g., exclude banned, only verified)
        public readonly ?Pagination $pagination = null,
        public readonly ?UserSortCriteria $sort = null
    ) {}

    public static function forPublicListing(int $perPage = 10, int $page = 1): self
    {
        return new self(
            publicOnly: true,
            pagination: new Pagination($page, $perPage),
            sort: UserSortCriteria::byCreationDateDesc() // Default sort for public
        );
    }

    public static function forAdminListing(
        ?string $name = null,
        ?string $email = null,
        ?string $role = null,
        bool $includeInactive = false,
        int $perPage = 20,
        int $page = 1,
        ?UserSortCriteria $sort = null
    ): self {
        return new self(
            name: $name ? new UserName($name) : null,
            email: $email ? new Email($email) : null,
            role: $role,
            includeInactive: $includeInactive,
            pagination: new Pagination($page, $perPage),
            sort: $sort ?? UserSortCriteria::byCreationDateDesc()
        );
    }

    public static function byUuid(EntityId $uuid): self
    {
        return new self(uuid: $uuid);
    }
}
