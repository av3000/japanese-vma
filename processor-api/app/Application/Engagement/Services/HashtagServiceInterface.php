<?php
namespace App\Application\Engagement\Services;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Shared\Results\Result;

interface HashtagServiceInterface
{
    /**
     * Get all hashtags for a specific entity.
     *
     * @param int $entityId The entity ID (article, list, etc.)
     * @param ObjectTemplateType $entityType The type of entity
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     */
    public function getHashtags(int $entityId, ObjectTemplateType $entityType): array;

    /**
     * Get hashtags for multiple entities in a single query (N+1 optimization).
     *
     * @param array<int> $entityIds Array of entity IDs
     * @param ObjectTemplateType $entityType The type of entities
     * @return array<int, array<object>> Associative array keyed by entity_id, values are arrays of hashtag objects
     */
    public function getBatchHashtags(array $entityIds, ObjectTemplateType $entityType): array;

    /**
     * Create multiple hashtags for an entity within a transaction.
     * Validates each tag before creation. Returns failure if any tag is invalid.
     *
     * @param int $entityId The entity to attach hashtags to
     * @param ObjectTemplateType $entityType The type of entity
     * @param array<string> $tags Array of hashtag strings (e.g., ['#php', '#laravel'])
     * @param int $userId The user creating the hashtags
     * @return Result Success data: null (void operation), Failure data: Error
     */
    public function createTagsForEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        array $tags,
        int $userId
    ): Result;

    /**
     * Replace all hashtags for an entity.
     * If tags array is empty, clears all existing hashtags.
     *
     * @param int $entityId The entity to sync hashtags for
     * @param ObjectTemplateType $entityType The type of entity
     * @param array<string> $tags Array of hashtag strings
     * @param int $userId The user performing the update
     * @return Result Success data: null (void operation), Failure data: Error
     */
    public function syncTagsForEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        array $tags,
        int $userId
    ): Result;
}
