<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Engagement\DTOs\{LikeCreateDTO, LikeFilterDTO};
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Domain\Engagement\Models\Like as DomainLike;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Like as PersistenceLike;
use App\Infrastructure\Persistence\Repositories\LikeMapper;
use App\Shared\Utils\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class LikeRepository implements LikeRepositoryInterface
{
    public function __construct(
        private readonly LikeMapper $likeMapper
    ) {}

    public function create(LikeCreateDTO $data): void
    {
        PersistenceLike::create($data);
    }

    public function findByFilter(LikeFilterDTO $filter): ?int
    {
        $query = $this->buildBaseQuery($filter);

        if ($filter->likeValue !== null) {
            $query->where('value', $filter->likeValue);
        }

        return $query->first()?->id;
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        PersistenceLike::where('real_object_id', $entityId)
            ->where('template_id', $entityTypeId)
            ->delete();
    }

    public function findAllByFilter(LikeFilterDTO $filter): Paginator
    {
        $query = PersistenceLike::query()
            ->where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId)
            ->with('user:id,uuid,name')
            ->orderBy('created_at');

        $perPage = $filter->pagination?->per_page ?? 15;
        $page = $filter->pagination?->page ?? 1;

        /** @var LengthAwarePaginator $paginated */
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $domainCollection = $paginated->getCollection()->map(function ($persistenceLike) {
            return $this->likeMapper->mapToDomain($persistenceLike);
        });

        $paginated->setCollection($domainCollection);

        return Paginator::fromEloquentPaginator($paginated, DomainLike::class);
    }

    private function buildBaseQuery(LikeFilterDTO $filter): Builder
    {
        return PersistenceLike::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId);
    }

    public function countByFilter(LikeFilterDTO $filter): int
    {
        $query = PersistenceLike::query()
            ->where('template_id', $filter->objectType->getLegacyId());

        if ($filter->entityId) {
            $query->where('real_object_id', $filter->entityId);
        }

        return $query->count();
    }

    public function userLikedByFilter(LikeFilterDTO $filter): bool
    {
        $query = $this->buildBaseQuery($filter);

        return $query->where('user_id', auth('api')->user()->id)
            ->exists(); // executes SELECT 1 ... LIMIT 1
    }
}
