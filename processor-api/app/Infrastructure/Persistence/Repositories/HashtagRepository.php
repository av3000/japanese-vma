<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Application\Engagement\DTOs\HashtagFilterDTO;
use App\Infrastructure\Persistence\Models\Hashtag;
use Illuminate\Database\Eloquent\Builder;

class HashtagRepository implements HashtagRepositoryInterface
{
    public function create($data): void
    {
        // TODO: Implementation for creating a hashtag, before that run job to add 'tag' column for 'hashtags' from 'uniquehashtags'
    }

    public function findAllByFilter(HashtagFilterDTO $filter): array
    {
        return $this->buildBaseQuery($filter)->get()->toArray();
    }

    private function buildBaseQuery(HashtagFilterDTO $filter): Builder
    {
        return Hashtag::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        $results = Hashtag::where('template_id', $objectType->getLegacyId())
            ->whereIn('real_object_id', $entityIds)
            ->get()
            ->groupBy('real_object_id')
            ->map->toArray()
            ->toArray();

        return $results;
    }
}
