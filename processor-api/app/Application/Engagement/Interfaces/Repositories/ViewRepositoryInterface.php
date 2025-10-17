<?php

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\DTOs\{ViewCreateDTO, ViewFilterDTO};

use App\Infrastructure\Persistence\Models\View;
use Illuminate\Database\Eloquent\Collection;

interface ViewRepositoryInterface
{
    public function create(ViewCreateDTO $data): void;
    public function findByFilter(ViewFilterDTO $filters): ?int;
    public function findAllByFilter(ViewFilterDTO $filters): Collection;
    public function updateTimestampById(int $viewId): void;
}
