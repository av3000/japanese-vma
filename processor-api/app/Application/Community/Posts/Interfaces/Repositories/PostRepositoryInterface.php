<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Interfaces\Repositories;

use App\Domain\Community\Posts\DTOs\PostPageDTO;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;

interface PostRepositoryInterface
{
    public function find(PostQueryCriteria $criteria): PostPageDTO;

    public function findByUuid(EntityId $uuid): ?Post;

    /**
     * Transitional: legacy numeric Post URLs resolve once. UUID stays canonical.
     */
    public function findByLegacyId(int $id): ?Post;
}
