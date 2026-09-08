<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Domain\Engagement\DTOs\LikeCreateDTO;
use App\Domain\Engagement\DTOs\LikeFilterDTO;
use App\Domain\Engagement\Models\Like as DomainLike;
use App\Infrastructure\Persistence\Models\Like as PersistenceLike;
use App\Shared\Utils\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class LikeRepository implements LikeRepositoryInterface
{
    public function __construct(
        private readonly LikeMapper $likeMapper
    ) {
    }

    public function create(LikeCreateDTO $data): void
    {
        PersistenceLike::create($data->toArray());
    }

    public function createIfAbsent(LikeCreateDTO $data): bool
    {
        // insertOrIgnore leans on the likes_user_target_unique index rather than on a
        // preceding SELECT, so a concurrent duplicate is dropped by the database
        // instead of raising a QueryException the caller would have to decode.
        $inserted = PersistenceLike::query()->insertOrIgnore([
            $data->toArray() + ['created_at' => now(), 'updated_at' => now()],
        ]);

        return $inserted > 0;
    }

    public function deleteForUserTarget(int $userId, int $templateId, int $realObjectId): int
    {
        return PersistenceLike::query()
            ->where('user_id', $userId)
            ->where('template_id', $templateId)
            ->where('real_object_id', $realObjectId)
            ->delete();
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
        if ($filter->userId === null) {
            return false;
        }

        return $this->buildBaseQuery($filter)
            ->where('user_id', $filter->userId)
            ->exists(); // executes SELECT 1 ... LIMIT 1
    }
}
