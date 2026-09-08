<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\LikeCreateDTO;
use App\Domain\Engagement\DTOs\LikeFilterDTO;
use App\Shared\Utils\Paginator;

interface LikeRepositoryInterface
{
    public function create(LikeCreateDTO $data): void;

    /**
     * Insert unless the (user, target) tuple already exists.
     *
     * Returns whether this call is the one that inserted. A false return means a
     * concurrent request won the race; the caller must treat the target as liked
     * either way, not retry.
     */
    public function createIfAbsent(LikeCreateDTO $data): bool;

    public function findByFilter(LikeFilterDTO $filter): ?int;

    /**
     * Remove this user's like of this target, if any, and report how many rows went.
     *
     * The count is the toggle's read of "was it liked": a delete-first toggle needs
     * no prior SELECT, so no window opens between the check and the write.
     */
    public function deleteForUserTarget(int $userId, int $templateId, int $realObjectId): int;

    public function deleteByEntity(int $entityId, int $entityTypeId): void;

    public function findAllByFilter(LikeFilterDTO $filter): Paginator;

    public function countByFilter(LikeFilterDTO $filter): int;

    public function userLikedByFilter(LikeFilterDTO $filter): bool;
}
