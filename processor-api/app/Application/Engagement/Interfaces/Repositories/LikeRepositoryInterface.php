<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\{LikeCreateDTO, LikeFilterDTO};
use App\Domain\Shared\Enums\ObjectTemplateType;

use App\Infrastructure\Persistence\Models\Like;
use Illuminate\Database\Eloquent\Collection;

interface LikeRepositoryInterface
{
    public function create(LikeCreateDTO $data): void;
    public function findByFilter(LikeFilterDTO $filter): ?int;
    public function deleteByEntity(int $entityId, int $entityTypeId): void;
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
    public function findAllByFilter(LikeFilterDTO $filter): array;
}
