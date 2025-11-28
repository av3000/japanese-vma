<?php

declare(strict_types=1);

namespace App\Application\CustomLists\Interfaces\Repositories;

use App\Domain\Shared\Enums\CustomListType;
use App\Domain\Shared\ValueObjects\UserId;

interface CustomListRepositoryInterface
{
    /**
     * Create default learning lists for a user
     *
     * @param UserId $userId
     * @return void
     */
    public function createDefaultListsForUser(UserId $userId): void;

    /**
     * Create a single custom list
     *
     * @param UserId $userId
     * @param CustomListType $type
     * @param string $title
     * @param string $description
     * @param bool $publicity
     * @return void
     */
    public function create(
        UserId $userId,
        CustomListType $type,
        string $title,
        string $description,
        bool $publicity = false
    ): void;
}
