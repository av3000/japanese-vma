<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Engagement\DTOs\{LikeCreateDTO, LikeFilterDTO};
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LikeRepository implements LikeRepositoryInterface
{
    public function create(LikeCreateDTO $data): void
    {
        Like::create($data); // just providing DTO works because it implements Arrayable
    }

    public function findByFilter(LikeFilterDTO $filter): ?int
    {
        $query = $this->buildBaseQuery($filter);

        if ($filter->likeValue !== null) {
            $query->where('value', $filter->likeValue);
        }

        return $query->first()?->id;
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        $results = Like::where('template_id', $objectType->getLegacyId())
            ->whereIn('real_object_id', $entityIds)
            ->get()
            ->groupBy('real_object_id')
            ->map->toArray() // Convert each group to array
            ->toArray();

        return $results;
    }

    public function findAllByFilter(LikeFilterDTO $filter): array
    {
        return $this->buildBaseQuery($filter)->get()->toArray();
    }

    private function buildBaseQuery(LikeFilterDTO $filter): Builder
    {
        return Like::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }
}
