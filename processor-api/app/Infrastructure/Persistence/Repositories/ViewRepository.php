<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Engagement\DTOs\{ViewCreateDTO, ViewFilterDTO};
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ViewRepository implements ViewRepositoryInterface
{
    public function create(ViewCreateDTO $data): void
    {
        View::create($data); // just providing DTO works because it implements Arrayable
    }

    public function findByFilter(ViewFilterDTO $filter): ?int
    {
        $query = $this->buildBaseQuery($filter);

        if ($filter->userId !== null) {
            $query->where('user_id', $filter->userId);
        } else {
            $query->where('user_ip', $filter->ipAddress)
                ->whereNull('user_id');
        }

        return $query->first()?->id;
    }

    public function findAllByFilter(ViewFilterDTO $filter): Collection
    {
        return $this->buildBaseQuery($filter)->get();
    }

    private function buildBaseQuery(ViewFilterDTO $filter): Builder
    {
        return View::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }

    public function updateTimestampById(int $viewId): void
    {
        View::where('id', $viewId)->touch();
    }
}
