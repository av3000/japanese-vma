<?php
namespace App\Application\Engagement\Services;

use App\Domain\Shared\Enums\ObjectTemplateType;

interface HashtagServiceInterface
{
    public function getHashtags(int $entityId, ObjectTemplateType $entityType): array;
    public function getBatchHashtags(array $entityIds, ObjectTemplateType $entityType): array;
}
