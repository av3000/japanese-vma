<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\{ViewCreateDTO, ViewFilterDTO};
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Database\Eloquent\Collection;

interface ViewRepositoryInterface
{
    public function create(ViewCreateDTO $data): void;
    public function findByFilter(ViewFilterDTO $filters): ?int;
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
    public function findAllByFilter(ViewFilterDTO $filters): array;
    public function deleteByEntity(int $entityId, int $entityTypeId): void;
    public function updateTimestampById(int $viewId): void;
    public function countByFilter(ViewFilterDTO $filter): int;
}
