<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Interfaces\Repositories;

use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostPageDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

interface PostRepositoryInterface
{
    public function find(PostQueryCriteria $criteria): PostPageDTO;

    public function findByUuid(EntityId $uuid): ?Post;

    /**
     * Transitional: legacy numeric Post URLs resolve once. UUID stays canonical.
     */
    public function findByLegacyId(int $id): ?Post;

    /**
     * The uuid is supplied by the caller so the application layer can name the
     * Post before the row exists and reload it by that identity afterwards.
     */
    public function create(PostCreateDTO $dto, UserId $authorId, EntityId $uuid): Post;

    public function update(int $postId, PostUpdateDTO $dto): void;

    public function setLocked(int $postId, bool $locked): void;

    public function delete(int $postId): void;
}
