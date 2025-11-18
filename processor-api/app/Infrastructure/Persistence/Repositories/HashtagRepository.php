<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Infrastructure\Persistence\Models\{HashtagEntity, Uniquehashtag};
use Illuminate\Database\Eloquent\Builder;

class HashtagRepository implements HashtagRepositoryInterface
{
    /**
     * Create a new hashtag association for an entity.
     *
     * First, creates or retrieves the unique hashtag by content.
     * Then, creates the association between the hashtag and the entity.
     *
     * @param array{entity_id: int, entity_type_id: int, content: string, user_id: int} $data
     * @return void
     * @throws \Illuminate\Database\QueryException On database constraint violation or connection failure
     */
    public function create(array $data): void
    {
        // Get or create the unique hashtag
        $uniquehashtag = Uniquehashtag::firstOrCreate(['content' => $data['content']]);

        // Create the association
        HashtagEntity::create([
            'hashtag_id' => $uniquehashtag->id,
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $data['entity_id'],
            'user_id' => $data['user_id'],
        ]);
    }

    /**
     * Find all hashtags for a specific entity using a filter DTO.
     *
     * Eager loads the uniquehashtag relationship to avoid N+1 queries.
     * Returns only the unique hashtag data, not the association records.
     *
     * @param HashtagFilterDTO $filter Filter criteria (entity_id, entity_type)
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findAllByFilter(HashtagFilterDTO $filter): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', $filter->entityType->getLegacyId())
            ->where('entity_id', $filter->entityId)
            ->get()
            ->map(fn($link) => $link->uniquehashtag)
            ->toArray();
    }

    /**
     * Delete all hashtag associations for a specific entity.
     *
     * Note: Does not delete the unique hashtag records themselves,
     * only the associations. Unique hashtags may be reused by other entities.
     *
     * @param int $entityId The entity ID
     * @param int $entityTypeId The entity type ID (legacy integer ID)
     * @return void
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        HashtagEntity::where('entity_id', $entityId)
            ->where('entity_type_id', $entityTypeId)
            ->delete();
    }

    /**
     * Build base query for hashtag filtering.
     *
     * @param HashtagFilterDTO $filter Filter criteria
     * @return Builder Eloquent query builder
     */
    private function buildBaseQuery(HashtagFilterDTO $filter): Builder
    {
        return HashtagEntity::where('entity_type_id', $filter->entityType->getLegacyId())
            ->where('entity_id', $filter->entityId);
    }

    /**
     * Find all hashtags for multiple entities (batch loading for N+1 optimization).
     *
     * Useful when loading hashtags for a paginated list of articles/lists/posts.
     * Returns hashtags grouped by entity_id for easy attachment.
     *
     * @param array<int> $entityIds Array of entity IDs to load hashtags for
     * @param ObjectTemplateType $entityType The type of entities
     * @return array<int, array<object>> Associative array keyed by entity_id
     * @throws \Illuminate\Database\QueryException On database failure
     * @example [123 => [{id: 1, content: '#php'}, {id: 2, content: '#laravel'}], 456 => [{id: 3, content: '#vue'}]]
     */
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
