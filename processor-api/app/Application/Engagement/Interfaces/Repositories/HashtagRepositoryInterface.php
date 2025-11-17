<?php
namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

interface HashtagRepositoryInterface
{
    /**
     * Create a new hashtag association for an entity.
     * Creates the unique hashtag if it doesn't exist, then creates the association.
     *
     * @param array{entity_id: int, entity_type_id: int, content: string, user_id: int} $data
     * @return void
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function create(array $data): void; // TODO: create CreateHashtagDTO

    /**
     * Delete all hashtags for a specific entity.
     * Used when deleting an entity or updating its hashtags.
     *
     * @param int $entityId The entity ID
     * @param int $entityTypeId The entity type ID (legacy integer ID)
     * @return void
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void;

    /**
     * Find all hashtags for a specific entity using a filter DTO.
     *
     * @param HashtagFilterDTO $filter Filter criteria (entity_id, entity_type)
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findAllByFilter(HashtagFilterDTO $filter): array;

    /**
     * Find all hashtags for multiple entities (batch loading for N+1 optimization).
     *
     * @param array<int> $entityIds Array of entity IDs to load hashtags for
     * @param ObjectTemplateType $objectType The type of entities
     * @return array<int, array<object>> Associative array keyed by entity_id, values are arrays of hashtag objects
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
}
