<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

class HashtagService implements HashtagServiceInterface
{
    public function __construct(
        private HashtagRepositoryInterface $hashtagRepository
    ) {}

    public function getHashtags(int $entityId, ObjectTemplateType $objectType): array
    {
        return $this->hashtagRepository->findAllByFilter(
            new HashtagFilterDTO(
                entityId: $entityId,
                objectType: $objectType,
            )
        );
    }

    public function getBatchHashtags(array $entityIds, ObjectTemplateType $entityType): array
    {
        return $this->hashtagRepository->findAllByEntityIds($entityIds, $entityType);
    }
}
