<?php
namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\HashtagFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

interface HashtagRepositoryInterface
{
    public function create($data): void; // TODO: create createHashtagDTO
    public function findAllByFilter(HashtagFilterDTO $filter): array;
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
}
