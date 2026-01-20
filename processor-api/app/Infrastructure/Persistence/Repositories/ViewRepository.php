<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Engagement\DTOs\{ViewCreateDTO, ViewFilterDTO};
use App\Domain\Shared\Enums\ObjectTemplateType;

use App\Infrastructure\Persistence\Models\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ViewRepository implements ViewRepositoryInterface
{
    public function create(ViewCreateDTO $createDto): void
    {
        View::create($createDto->toArray());
    }

    public function findByFilter(ViewFilterDTO $filter): ?int
    {
        $query = $this->buildBaseQuery($filter);

        if ($filter->userId !== null) {
            $query->where('user_id', $filter->userId);
        } else {
            $query->where('user_ip', $filter->ipAddress)
                ->whereNull('user_id');
        }

        return $query->first()?->id;
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        $results = View::where('template_id', $objectType->getLegacyId())
            ->whereIn('real_object_id', $entityIds)
            ->get()
            ->groupBy('real_object_id')
            ->map->toArray() // Convert each group to array
            ->toArray();

        return $results;
    }

    public function findAllByFilter(ViewFilterDTO $filter): array
    {
        return $this->buildBaseQuery($filter)->get()->toArray();
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        View::where('real_object_id', $entityId)
            ->where('template_id', $entityTypeId)
            ->delete();
    }

    private function buildBaseQuery(ViewFilterDTO $filter): Builder
    {
        return View::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }

    public function updateTimestampById(int $viewId): void
    {
        View::where('id', $viewId)->touch();
    }

    public function countByFilter(ViewFilterDTO $filter): int
    {
        $query = View::query()
            ->where('template_id', $filter->objectType->getLegacyId());

        if ($filter->entityId) {
            $query->where('real_object_id', $filter->entityId);
        }

        return $query->count(); // executes SQL COUNT(*), extremely fast
    }
}
