<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;
use App\Domain\Comments\DTOs\CommentPageDTO;
use App\Domain\Comments\DTOs\CommentPaginationDTO;
use App\Domain\Comments\DTOs\CommentRepliesPreviewDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Queries\CommentQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Infrastructure\Persistence\Builders\CommentDescendantsQueryBuilder;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Repositories\CommentMapper;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent implementation of the comment thread read port.
 *
 * Everything framework-shaped about reading a thread lives here: the query
 * builder, ordering and pagination. Callers above this class see only
 * application and domain types.
 *
 * The reply-tree walk itself lives in CommentDescendantsQueryBuilder, shared
 * with nothing else that reads it, so counts and rows cannot drift apart.
 */
final readonly class DatabaseCommentThreadReader implements CommentThreadReaderInterface
{
    public function __construct(
        private CommentDescendantsQueryBuilder $descendants,
    ) {
    }

    public function rootPage(
        int $entityId,
        ObjectTemplateType $entityType,
        CommentQueryCriteria $criteria,
        ?int $viewerUserId,
    ): CommentPageDTO {
        $direction = $criteria->sort->direction->value;

        $query = PersistenceComment::with(['user'])
            ->where('template_id', $entityType->getLegacyId())
            ->where('real_object_id', $entityId)
            // Replies belong to the comment they answer, not to the page. Without
            // this, the total counts rows instead of conversations and a reply can
            // land on a different page than its parent.
            ->whereNull('parent_comment_id')
            ->orderBy($criteria->sort->field->value, $direction)
            // `created_at` has second granularity on legacy rows, so ties are
            // common and would otherwise paginate non-deterministically.
            ->orderBy('id', $direction);

        $this->applyLikeEnrichment($query, $viewerUserId);

        $paginator = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        return new CommentPageDTO(
            comments: $this->mapRows($paginator->getCollection()->all()),
            pagination: new CommentPaginationDTO(
                page: $paginator->currentPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
                lastPage: $paginator->lastPage(),
                hasMore: $paginator->hasMorePages(),
            ),
        );
    }

    public function replyPreviews(array $rootIds, int $limitPerRoot, ?int $viewerUserId): array
    {
        $ranked = $this->descendants->rankedDescendants($rootIds, $limitPerRoot);

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

        $previews = [];

        foreach ($countByRoot as $rootId => $count) {
            $previews[$rootId] = new CommentRepliesPreviewDTO(
                count: $count,
                replies: $this->orderedByIds($previewIdsByRoot[$rootId] ?? [], $repliesById),
            );
        }

        return $previews;
    }

    public function replyPage(int $rootId, Pagination $pagination, ?int $viewerUserId): CommentPageDTO
    {
        // Paged in the database rather than by loading every descendant id and
        // filtering on it: a busy thread would otherwise build an IN list the
        // size of the whole subtree to render one page of it.
        ['ids' => $ids, 'total' => $total] = $this->descendants->pageOfDescendants($rootId, $pagination);

        $commentsById = $this->loadCommentsById($ids, $viewerUserId);
        $perPage = $pagination->per_page;

        return new CommentPageDTO(
            comments: $this->orderedByIds($ids, $commentsById),
            pagination: new CommentPaginationDTO(
                page: $pagination->page,
                perPage: $perPage,
                total: $total,
                lastPage: max(1, (int) ceil($total / $perPage)),
                hasMore: $pagination->offset() + count($ids) < $total,
            ),
        );
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
     * @param int[] $commentIds
     *
     * @return array<int, DomainComment> keyed by comment id
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
     * Restore the order the id query established; the hydrating query does not
     * preserve it, and a row that vanished between the two is dropped.
     *
     * @param int[] $ids
     * @param array<int, DomainComment> $byId
     *
     * @return array<int, DomainComment>
     */
    private function orderedByIds(array $ids, array $byId): array
    {
        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * @param array<int, PersistenceComment> $rows
     *
     * @return array<int, DomainComment>
     */
    private function mapRows(array $rows): array
    {
        return array_values(array_map(
            static fn (PersistenceComment $comment): DomainComment => CommentMapper::mapToDomain($comment),
            $rows,
        ));
    }
}
