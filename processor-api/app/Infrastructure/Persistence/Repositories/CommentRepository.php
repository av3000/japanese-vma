<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * Recursion guard for the reply walk.
     *
     * The schema puts no ceiling on nesting, so a cycle introduced by bad data
     * would otherwise spin forever inside the database. Ten levels is far past
     * anything the UI renders; rows deeper than that are counted out rather
     * than hanging the request.
     */
    private const MAX_REPLY_DEPTH = 10;

    /**
     * Sort columns the request layer has already narrowed. Re-stated here so a
     * future caller that bypasses the FormRequest cannot reach `orderBy` with
     * arbitrary input.
     */
    private const SORTABLE_COLUMNS = ['created_at', 'updated_at'];

    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, ?int $viewerUserId): Comments
    {
        $sortColumn = in_array($criteria->sortBy, self::SORTABLE_COLUMNS, true)
            ? $criteria->sortBy
            : 'created_at';
        $sortDirection = strtolower($criteria->sortDir) === 'asc' ? 'asc' : 'desc';

        $query = PersistenceComment::with(['user'])
            ->where('template_id', $criteria->entityType->getLegacyId())
            ->where('real_object_id', $criteria->entityId)
            // Replies belong to the comment they answer, not to the page. Without
            // this, `pagination.total` counts rows instead of conversations and a
            // reply can land on a different page than its parent.
            ->whereNull('parent_comment_id')
            ->orderBy($sortColumn, $sortDirection)
            // `created_at` has second granularity on legacy rows, so ties are
            // common and would otherwise paginate non-deterministically.
            ->orderBy('id', $sortDirection);

        $this->applyLikeEnrichment($query, $viewerUserId);

        $pagination = $criteria->pagination ?? Pagination::default();

        $paginatedResults = $query->paginate(
            $pagination->per_page,
            ['*'],
            'page',
            $pagination->page
        );

        $domainComments = $paginatedResults->getCollection()
            ->map(static fn (PersistenceComment $comment) => CommentMapper::mapToDomain($comment));

        $paginatedResults->setCollection($domainComments);

        return Comments::fromEloquentPaginator($paginatedResults);
    }

    /**
     * @param int[] $rootIds
     *
     * @return array<int, array{count: int, replies: DomainComment[]}>
     */
    public function findRepliesForRoots(array $rootIds, int $limitPerRoot, ?int $viewerUserId): array
    {
        if ($rootIds === []) {
            return [];
        }

        // One row per previewed reply, carrying its root's whole subtree size.
        //
        // `rank_limit` is at least 1 even when the caller wants no previews: the
        // per-root total travels on a returned row, so dropping every row would
        // drop the counts with it. The unwanted previews are discarded below.
        $rankLimit = max($limitPerRoot, 1);

        // Positional placeholders rather than a Postgres array literal: `?::int[]`
        // would put a `:int` sequence in front of PDO's placeholder scanner.
        $rootPlaceholders = implode(',', array_fill(0, count($rootIds), '?'));

        $ranked = DB::select(
            <<<SQL
            WITH RECURSIVE descendants AS (
                SELECT c.id, c.parent_comment_id AS root_id, 1 AS depth, c.created_at
                FROM comments c
                WHERE c.parent_comment_id IN ({$rootPlaceholders})
                UNION ALL
                SELECT c.id, d.root_id, d.depth + 1, c.created_at
                FROM comments c
                INNER JOIN descendants d ON c.parent_comment_id = d.id
                WHERE d.depth < ?
            ),
            ranked AS (
                SELECT
                    id,
                    root_id,
                    ROW_NUMBER() OVER (PARTITION BY root_id ORDER BY created_at ASC, id ASC) AS rn,
                    COUNT(*) OVER (PARTITION BY root_id) AS total
                FROM descendants
            )
            SELECT id, root_id, total
            FROM ranked
            WHERE rn <= ?
            SQL,
            [...array_values($rootIds), self::MAX_REPLY_DEPTH, $rankLimit],
        );

        if ($ranked === []) {
            return [];
        }

        /** @var array<int, int> $countByRoot */
        $countByRoot = [];
        /** @var array<int, int[]> $previewIdsByRoot */
        $previewIdsByRoot = [];
        /** @var int[] $allPreviewIds */
        $allPreviewIds = [];

        foreach ($ranked as $row) {
            $rootId = (int) $row->root_id;
            $countByRoot[$rootId] = (int) $row->total;

            if ($limitPerRoot > 0) {
                $previewIdsByRoot[$rootId][] = (int) $row->id;
                $allPreviewIds[] = (int) $row->id;
            }
        }

        $repliesById = $this->loadCommentsById($allPreviewIds, $viewerUserId);

        $result = [];

        foreach ($countByRoot as $rootId => $count) {
            $result[$rootId] = [
                'count' => $count,
                'replies' => array_values(array_filter(array_map(
                    static fn (int $replyId) => $repliesById[$replyId] ?? null,
                    $previewIdsByRoot[$rootId] ?? [],
                ))),
            ];
        }

        return $result;
    }

    public function findRepliesByRoot(int $rootId, Pagination $pagination, ?int $viewerUserId): Comments
    {
        $descendantIds = $this->descendantIdsInReadOrder($rootId);

        $query = PersistenceComment::with(['user'])
            ->whereIn('id', $descendantIds === [] ? [0] : $descendantIds)
            // A reply thread reads forwards, unlike the newest-first root list.
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        $this->applyLikeEnrichment($query, $viewerUserId);

        $paginatedResults = $query->paginate(
            $pagination->per_page,
            ['*'],
            'page',
            $pagination->page
        );

        $paginatedResults->setCollection(
            $paginatedResults->getCollection()
                ->map(static fn (PersistenceComment $comment) => CommentMapper::mapToDomain($comment))
        );

        return Comments::fromEloquentPaginator($paginatedResults);
    }

    public function findByUuid(EntityId $commentUuid): ?DomainComment
    {
        $entity = PersistenceComment::with('user')
            ->withCount('likes')
            ->where('uuid', $commentUuid->value())
            ->first();

        return $entity ? CommentMapper::mapToDomain($entity) : null;
    }

    public function findById(int $commentId): ?DomainComment
    {
        $entity = PersistenceComment::with('user')
            ->withCount('likes')
            ->find($commentId);

        return $entity ? CommentMapper::mapToDomain($entity) : null;
    }

    public function createForEntity(CommentCreateDTO $dto, UserId $authorId): DomainComment
    {
        $persistenceComment = PersistenceComment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => $dto->entity_type->getLegacyId(),
            'real_object_id' => $dto->entity_id,
            'real_object_uuid' => $dto->entity_uuid->value(),
            'entity_type_uuid' => $dto->entity_type->value,
            'user_id' => $authorId->value(),
            'parent_comment_id' => $dto->parent_comment_id,
            'content' => $dto->content,
        ]);

        return $this->reload($persistenceComment->id);
    }

    public function updateContent(int $commentId, string $content): DomainComment
    {
        $persistenceComment = PersistenceComment::query()->find($commentId);

        if ($persistenceComment === null) {
            throw new RuntimeException("Comment {$commentId} disappeared before it could be updated");
        }

        $persistenceComment->content = $content;
        $persistenceComment->save();

        return $this->reload($commentId);
    }

    /**
     * Breadth-first walk of the reply tree, returning deepest levels first so
     * callers can delete children before their parents.
     *
     * Kept separate from the read-side walk: deletes need deepest-first
     * ordering and no enrichment, reads need chronological order and both.
     *
     * @return int[]
     */
    public function collectDescendantIds(int $commentId): array
    {
        $levels = [];
        $currentLevel = [$commentId];

        while ($currentLevel !== []) {
            $childIds = PersistenceComment::query()
                ->whereIn('parent_comment_id', $currentLevel)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($childIds === []) {
                break;
            }

            $levels[] = $childIds;
            $currentLevel = $childIds;
        }

        return $levels === [] ? [] : array_merge(...array_reverse($levels));
    }

    /**
     * @param int[] $commentIds
     */
    public function deleteWithLikesByIds(array $commentIds): void
    {
        if ($commentIds === []) {
            return;
        }

        Like::query()
            ->where('template_id', ObjectTemplateType::COMMENT->getLegacyId())
            ->whereIn('real_object_id', $commentIds)
            ->delete();

        PersistenceComment::query()
            ->whereIn('id', $commentIds)
            ->delete();
    }

    /**
     * Replies are stored against the same entity as the comment they answer,
     * so selecting by entity already covers the whole thread at every depth.
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        $commentIds = PersistenceComment::query()
            ->where('real_object_id', $entityId)
            ->where('template_id', $entityTypeId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->deleteWithLikesByIds($commentIds);
    }

    /**
     * `likes_count` is always present; `is_liked_by_viewer` only exists for an
     * authenticated reader, and defaults to false in the mapper otherwise.
     *
     * @param Builder<PersistenceComment> $query
     */
    private function applyLikeEnrichment(Builder $query, ?int $viewerUserId): void
    {
        $query->withCount('likes');

        if ($viewerUserId !== null) {
            $query->withExists(['likes as is_liked_by_viewer' => function ($likeQuery) use ($viewerUserId) {
                $likeQuery->where('user_id', $viewerUserId);
            }]);
        }
    }

    /**
     * Every descendant of one comment, at any depth, in one query.
     *
     * @return int[]
     */
    private function descendantIdsInReadOrder(int $rootId): array
    {
        $rows = DB::select(
            <<<'SQL'
            WITH RECURSIVE descendants AS (
                SELECT c.id, 1 AS depth
                FROM comments c
                WHERE c.parent_comment_id = ?
                UNION ALL
                SELECT c.id, d.depth + 1
                FROM comments c
                INNER JOIN descendants d ON c.parent_comment_id = d.id
                WHERE d.depth < ?
            )
            SELECT id FROM descendants
            SQL,
            [$rootId, self::MAX_REPLY_DEPTH],
        );

        return array_map(static fn ($row) => (int) $row->id, $rows);
    }

    /**
     * @param int[] $commentIds
     *
     * @return array<int, DomainComment> Keyed by comment id.
     */
    private function loadCommentsById(array $commentIds, ?int $viewerUserId): array
    {
        if ($commentIds === []) {
            return [];
        }

        $query = PersistenceComment::with(['user'])->whereIn('id', $commentIds);

        $this->applyLikeEnrichment($query, $viewerUserId);

        $byId = [];

        foreach ($query->get() as $comment) {
            $byId[(int) $comment->id] = CommentMapper::mapToDomain($comment);
        }

        return $byId;
    }

    /**
     * Total descendants of one comment, at any depth.
     */
    private function descendantCount(int $rootId): int
    {
        $rows = DB::select(
            <<<'SQL'
            WITH RECURSIVE descendants AS (
                SELECT c.id, 1 AS depth
                FROM comments c
                WHERE c.parent_comment_id = ?
                UNION ALL
                SELECT c.id, d.depth + 1
                FROM comments c
                INNER JOIN descendants d ON c.parent_comment_id = d.id
                WHERE d.depth < ?
            )
            SELECT COUNT(*) AS total FROM descendants
            SQL,
            [$rootId, self::MAX_REPLY_DEPTH],
        );

        return (int) ($rows[0]->total ?? 0);
    }

    /**
     * Re-read a comment after a write, carrying its subtree size.
     *
     * The count is not decoration: an edit response that reported
     * `replies_count: 0` for a comment with twelve replies would be a value a
     * client cannot use and cannot distinguish from the truth.
     */
    private function reload(int $commentId): DomainComment
    {
        $entity = PersistenceComment::with('user')
            ->withCount('likes')
            ->findOrFail($commentId);

        return CommentMapper::mapToDomain($entity)
            ->withReplies([], $this->descendantCount($commentId));
    }
}
