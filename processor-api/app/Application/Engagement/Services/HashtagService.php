<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Hashtags\Errors\HashtagErrors;
use App\Shared\Results\Result;


class HashtagService implements HashtagServiceInterface
{
    public function __construct(
        private HashtagRepositoryInterface $hashtagRepository
    ) {}

    /**
     * Get all hashtags for a specific entity.
     *
     * @param int $entityId The entity ID (article, list, etc.)
     * @param ObjectTemplateType $entityType The type of entity
     * @return array<object> Array of hashtag objects with properties: id, content, created_at, updated_at
     */
    public function getHashtags(int $entityId, ObjectTemplateType $entityType): array
    {
        return $this->hashtagRepository->findAllByFilter(
            new HashtagFilterDTO(
                entityId: $entityId,
                entityType: $entityType,
            )
        );
    }

    /**
     * Get hashtags for multiple entities in a single query (N+1 optimization).
     * Useful for batch loading hashtags for a list of articles/lists/posts.
     *
     * @param array<int> $entityIds Array of entity IDs
     * @param ObjectTemplateType $entityType The type of entities
     * @return array<int, array<object>> Associative array keyed by entity_id, values are arrays of hashtag objects
     * @example [123 => [{id: 1, content: '#php'}, {id: 2, content: '#laravel'}], 456 => [{id: 3, content: '#vue'}]]
     */
    public function getBatchHashtags(array $entityIds, ObjectTemplateType $entityType): array
    {
        return $this->hashtagRepository->findAllByEntityIds($entityIds, $entityType);
    }

    /**
     * Create multiple hashtags for an entity within a transaction.
     *
     * Validates all tags before creation (sanitization, censorship check).
     * If any tag is invalid, returns failure without creating any hashtags.
     * If validation passes, creates all hashtags in a single transaction.
     *
     * @param int $entityId The entity to attach hashtags to
     * @param ObjectTemplateType $entityType The type of entity
     * @param array<string> $tags Array of hashtag strings (e.g., ['#php', '#laravel'])
     * @param int $userId The user creating the hashtags
     * @return Result Success data: null (void operation), Failure data: Error (HashtagErrors::invalidTag or HashtagErrors::creationFailed)
     */
    public function createTagsForEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        array $tags,
        int $userId
    ): Result {
        foreach($tags as $tag) {
            if(!$this->isValidTag($tag)) {
                return Result::failure(HashtagErrors::invalidTag($tag));
            }
        }

        try {
            \DB::transaction(function () use ($entityId, $entityType, $tags, $userId) {
                foreach ($tags as $tag) {
                    $this->hashtagRepository->create([
                        'entity_id' => $entityId,
                        'entity_type_id' => $entityType->getLegacyId(),
                        'content' => $tag,
                        'user_id' => $userId
                    ]);
                }
            });

            return Result::success();

        } catch (\Exception $e) {
            \Log::error('Hashtag creation failed', [
                'entity_id' => $entityId,
                'entity_type' => $entityType->value,
                'tags' => $tags,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(HashtagErrors::creationFailed());
        }
    }

    /**
     * Validate a hashtag against business rules.
     *
     * Rules:
     * - Must not contain dangerous characters (<, >, ", ', \, &)
     * - Must not contain censored words (spam, stupid, shit, etc.)
     * - Must not be empty after sanitization
     *
     * @param string $tag The hashtag to validate (with or without # prefix)
     * @return bool True if valid, false otherwise
     */
    private function isValidTag(string $tag): bool
    {
        $cleaned = preg_replace('/[<>"\'\\\&]/', '', $tag);

        $censored = ['spam', 'stupid', 'shit']; // potential examples for further implementation
        $lowerTag = strtolower($cleaned);

        foreach ($censored as $word) {
            if (str_contains($lowerTag, $word)) {
                return false;
            }
        }

        // Must have content after cleaning
        return !empty(trim($cleaned));
    }
}
