<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Domain\Engagement\DTOs\{DownloadCreateDTO, DownloadFilterDTO};
use App\Infrastructure\Persistence\Models\Download;
use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DownloadRepository implements DownloadRepositoryInterface
{
    public function create(DownloadCreateDTO $data): void
    {
        Download::create($data); // just providing DTO works because it implements Arrayable
    }

    public function findByFilter(DownloadFilterDTO $filter): ?int
    {
        $query = $this->buildBaseQuery($filter);

        if ($filter->likeValue !== null) {
            $query->where('value', $filter->likeValue);
        }

        return $query->first()?->id;
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        $results = Download::where('template_id', $objectType->getLegacyId())
            ->whereIn('real_object_id', $entityIds)
            ->get()
            ->groupBy('real_object_id')
            ->map->toArray() // Convert each group to array
            ->toArray();

        return $results;
    }

    public function findAllByFilter(DownloadFilterDTO $filter): array
    {
        return $this->buildBaseQuery($filter)->get()->toArray();
    }

    private function buildBaseQuery(DownloadFilterDTO $filter): Builder
    {
        return Download::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }
}
