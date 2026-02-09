<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\{LikeCreateDTO, LikeFilterDTO};
use App\Shared\Utils\Paginator;

interface LikeRepositoryInterface
{
    public function create(LikeCreateDTO $data): void;
    public function findByFilter(LikeFilterDTO $filter): ?int;
    public function deleteByEntity(int $entityId, int $entityTypeId): void;
    public function findAllByFilter(LikeFilterDTO $filter): Paginator;
    public function countByFilter(LikeFilterDTO $filter): int;
    public function userLikedByFilter(LikeFilterDTO $filter): bool;
}
