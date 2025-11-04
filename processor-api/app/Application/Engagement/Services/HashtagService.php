<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

class HashtagService implements HashtagServiceInterface
{
    public function __construct(
        private HashtagRepositoryInterface $hashtagRepository
    ) {}

    public function getHashtags(int $entityId, ObjectTemplateType $entityType): array
    {
        return $this->hashtagRepository->findAllByFilter(
            new HashtagFilterDTO(
                entityId: $entityId,
                entityType: $entityType,
            )
        );
    }

    public function getBatchHashtags(array $entityIds, ObjectTemplateType $entityType): array
    {
        return $this->hashtagRepository->findAllByEntityIds($entityIds, $entityType);
    }

    public function createTagsForEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        array $tags,
        int $userId
    ): void {
        foreach ($tags as $tag) {
            $this->hashtagRepository->create([
                'entity_id' => $entityId,
                'entity_type_id' => $entityType->getLegacyId(),
                'content' => $tag,
                'user_id' => $userId
            ]);
        }
    }
}
