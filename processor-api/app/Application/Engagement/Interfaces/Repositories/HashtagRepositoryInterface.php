<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\HashtagDuplicateGroupDTO;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Engagement\DTOs\HashtagMergeResultDTO;
use App\Domain\Engagement\DTOs\HashtagTagDTO;
use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Domain\Shared\Enums\ObjectTemplateType;

interface HashtagRepositoryInterface
{
    /**
     * Create a new hashtag association for an entity.
     * Creates the unique hashtag if it doesn't exist, then creates the association.
     *
     * @param array{entity_id: int, entity_type_id: int, content: string, user_id: int} $data
     *
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function create(array $data): void; // TODO: create CreateHashtagDTO

    public function findTag(int $hashtagId): ?HashtagTagDTO;

    /**
     * Another tag with the same comparison key, if one exists.
     */
    public function findTagByKey(string $key, ?int $exceptId = null): ?HashtagTagDTO;

    public function rename(int $hashtagId, HashtagName $name): HashtagTagDTO;

    /**
     * Groups of tags that share a normalised key, ordered by key.
     *
     * @return list<HashtagDuplicateGroupDTO>
     */
    public function findDuplicateGroups(): array;

    /**
     * Move every link from source to target and delete source, under row locks on both tags.
     * Active source links that would duplicate an active target link are soft-deleted as they
     * move, so the history survives and no entity ends up tagged twice. Call inside a
     * transaction.
     */
    public function merge(int $sourceId, int $targetId): HashtagMergeResultDTO;

    /**
     * Delete all hashtags for a specific entity.
     * Used when deleting an entity or updating its hashtags.
     *
     * @param int $entityId The entity ID
     * @param int $entityTypeId The entity type ID (legacy integer ID)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void;

    /**
     * Find all hashtags for a specific entity using a filter DTO.
     *
     * @param HashtagFilterDTO $filter Filter criteria (entity_id, entity_type)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     */
    public function findAllByFilter(HashtagFilterDTO $filter): array;

    /**
     * Find all hashtags for multiple entities (batch loading for N+1 optimization).
     *
     * @param array<int> $entityIds Array of entity IDs to load hashtags for
     * @param ObjectTemplateType $objectType The type of entities
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<int, array<object>> Associative array keyed by entity_id, values are arrays of hashtag objects
     */
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
}
