<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostPageDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Models\Post as DomainPost;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly PostMapper $postMapper,
    ) {
    }

    public function find(PostQueryCriteria $criteria): PostPageDTO
    {
        $query = PersistencePost::query()->with('author');

        $this->applyFilters($query, $criteria);
        $this->applySort($query, $criteria->sort);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $items = $paginator->getCollection()
            ->map(fn (PersistencePost $post): DomainPost => $this->postMapper->mapToDomain($post))
            ->all();

        return new PostPageDTO(
            items: $items,
            pagination: [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        );
    }

    public function findByUuid(EntityId $uuid): ?DomainPost
    {
        $post = PersistencePost::query()
            ->with('author')
            ->where('uuid', $uuid->value())
            ->first();

        return $post ? $this->postMapper->mapToDomain($post) : null;
    }

    public function findByLegacyId(int $id): ?DomainPost
    {
        $post = PersistencePost::query()
            ->with('author')
            ->whereKey($id)
            ->first();

        return $post ? $this->postMapper->mapToDomain($post) : null;
    }

    public function create(PostCreateDTO $dto, UserId $authorId, EntityId $uuid): DomainPost
    {
        $post = PersistencePost::create([
            'uuid' => $uuid->value(),
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $authorId->value(),
            // `posts.type` is a string column holding the numeric topic code.
            'type' => (string) $dto->topic->value,
            'title' => $dto->title,
            'content' => $dto->content,
            'locked' => false,
        ]);

        return $this->postMapper->mapToDomain($post->load('author'));
    }

    public function update(int $postId, PostUpdateDTO $dto): void
    {
        $attributes = $dto->toAttributes();

        if ($attributes === []) {
            return;
        }

        $this->postOrFail($postId)->update($attributes);
    }

    public function setLocked(int $postId, bool $locked): void
    {
        $this->postOrFail($postId)->update(['locked' => $locked]);
    }

    public function delete(int $postId): void
    {
        $this->postOrFail($postId)->delete();
    }

    /**
     * Writes go through the model rather than a mass `update()` on the query so
     * `updated_at` moves - the list `new` sort and the detail response both read
     * timestamps the caller expects to change.
     */
    private function postOrFail(int $postId): PersistencePost
    {
        return PersistencePost::query()->whereKey($postId)->first()
            ?? throw new RuntimeException("Post {$postId} no longer exists.");
    }

    /**
     * @param Builder<PersistencePost> $query
     */
    private function applyFilters(Builder $query, PostQueryCriteria $criteria): void
    {
        if ($criteria->keyword !== null) {
            $keyword = $criteria->keyword;

            // ILIKE keeps the legacy MySQL case-insensitive behaviour the
            // PostgreSQL migration would otherwise have dropped silently.
            $query->where(function (Builder $keywordQuery) use ($keyword): void {
                $keywordQuery->where('title', 'ILIKE', "%{$keyword}%")
                    ->orWhere('content', 'ILIKE', "%{$keyword}%");
            });
        }

        if ($criteria->hashtag !== null) {
            $hashtag = $criteria->hashtag;

            $query->whereIn('posts.id', function (QueryBuilder $subQuery) use ($hashtag): void {
                $subQuery->select('hashtag_entity.entity_id')
                    ->from('hashtag_entity')
                    ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
                    ->where('hashtag_entity.entity_type_id', $this->postTemplateId())
                    ->whereNull('hashtag_entity.deleted_at')
                    ->where('uniquehashtags.content', $hashtag);
            });
        }

        if ($criteria->topic !== null) {
            // `posts.type` is a string column holding the numeric topic code.
            $query->where('type', (string) $criteria->topic->value);
        }
    }

    /**
     * @param Builder<PersistencePost> $query
     */
    private function applySort(Builder $query, PostSort $sort): void
    {
        if ($sort === PostSort::POPULAR) {
            // Legacy sortByViewsTotal() used leftJoin + where + groupBy, which
            // dropped zero-view Posts and is invalid on PostgreSQL. A correlated
            // subquery keeps every Post and stays deterministic.
            $query->select('posts.*')
                ->selectSub(
                    DB::table('views')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('views.real_object_id', 'posts.id')
                        ->where('views.template_id', $this->postTemplateId()),
                    'views_total',
                )
                ->orderByDesc('views_total');
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * Post engagement and hashtag links key on the object-template id. Task 0 of
     * the migration plan gates that `objecttemplates.post.id` equals this legacy
     * id, which is also what LoadEntityStatsAction is called with.
     */
    private function postTemplateId(): int
    {
        return ObjectTemplateType::POST->getLegacyId();
    }
}
