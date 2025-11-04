<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\{DownloadCreateDTO, DownloadFilterDTO};
use App\Domain\Shared\Enums\ObjectTemplateType;

use App\Infrastructure\Persistence\Models\Download;
use Illuminate\Database\Eloquent\Collection;

interface DownloadRepositoryInterface
{
    public function create(DownloadCreateDTO $data): void;
    public function findByFilter(DownloadFilterDTO $filter): ?int;
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
    public function findAllByFilter(DownloadFilterDTO $filter): array;
}
