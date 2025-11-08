<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Infrastructure\Persistence\Models\{HashtagEntity, Uniquehashtag};

class HashtagRepository implements HashtagRepositoryInterface
{
    public function create($data): void
    {
        $uniquehashtag = Uniquehashtag::firstOrCreate(['content' => $data['content']]);

        HashtagEntity::create([
            'hashtag_id' => $uniquehashtag->id,
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $data['entity_id'],
            'user_id' => $data['user_id'],
        ]);
    }

    public function findAllByFilter(HashtagFilterDTO $filter): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', $filter->entityType->getLegacyId())
            ->where('entity_id', $filter->entityId)
            ->get()
            ->map(fn($link) => $link->uniquehashtag)
            ->toArray();
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        HashtagEntity::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->delete();
    }

    private function buildBaseQuery(HashtagFilterDTO $filter): Builder
    {
        return HashtagEntity::where('entity_type_id', $filter->entityType->getLegacyId())
            ->where('entity_id', $filter->entityId);
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $entityType): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', $entityType->getLegacyId())
            ->whereIn('entity_id', $entityIds)
            ->get()
            ->groupBy('entity_id')
            ->map(fn($links) => $links->map(fn($link) => $link->uniquehashtag))
            ->toArray();
    }
}
