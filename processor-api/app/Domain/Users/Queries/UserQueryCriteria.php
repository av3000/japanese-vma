<?php

declare(strict_types=1);

namespace App\Domain\Users\Queries;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserSortCriteria;

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
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?UserSortCriteria $sort = null
    ) {}

    public static function forPublicListing(int $perPage = 10, ?UserSortCriteria $sort = null, int $page = 1, int $limit = 20, int $offset = 0): self
    {
        return new self(
            publicOnly: true,
            pagination: new Pagination(page: $page, per_page: $perPage),
            sort: $sort ?? UserSortCriteria::byCreationDateDesc(),
            limit: $limit,
            offset: $offset
        );
    }

    // TODO: im not sure if search params should have strict value objects, as especially wildcard searches with partial matches
    public static function forAdminListing(
        ?string $uuid = null,
        ?string $name = null,
        ?string $email = null,
        ?string $role = null,
        bool $includeInactive = false,
        int $perPage = 20,
        int $page = 1,
        int $limit = 20,
        int $offset = 0,
        ?UserSortCriteria $sort = null
    ): self {
        return new self(
            uuid: $uuid ? new EntityId($uuid) : null,
            name: $name ? new UserName($name) : null,
            email: $email ? new Email($email) : null,
            role: $role,
            includeInactive: $includeInactive,
            pagination: new Pagination(page: $page, per_page: $perPage),
            sort: $sort ?? UserSortCriteria::byCreationDateDesc(),
            limit: $limit,
            offset: $offset
        );
    }

    public static function byUuid(EntityId $uuid): self
    {
        return new self(uuid: $uuid);
    }
}
