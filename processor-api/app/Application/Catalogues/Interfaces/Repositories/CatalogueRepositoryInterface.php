<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Interfaces\Repositories;

use App\Domain\Shared\Enums\CatalogueType;
use App\Domain\Shared\ValueObjects\UserId;

interface CatalogueRepositoryInterface
{
    /**
     * Create default learning lists for a user
     *
     * @param UserId $userId
     * @return void
     */
    public function createDefaultCatalogueForUser(UserId $userId): void;

    /**
     * Create a single custom list
     *
     * @param UserId $userId
     * @param CatalogueType $type
     * @param string $title
     * @param string $description
     * @param bool $publicity
     * @return void
     */
    public function create(
        UserId $userId,
        CatalogueType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void;
}
