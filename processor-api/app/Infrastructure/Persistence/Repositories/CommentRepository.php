<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Repositories\CommentMapper;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Engagement\DTOs\CommentFilterDTO;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Http\Models\{ObjectTemplate, User};
use App\Infrastructure\Persistence\Models\Like;
use Illuminate\Support\Facades\DB;

class CommentRepository implements CommentRepositoryInterface
{
    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, string $entityId, int $userId): Comments
    {
        $query = PersistenceComment::with(['user'])
            ->where('template_id', $criteria->entityType->getLegacyId())
            ->where('real_object_id', $entityId)
            ->orderBy('created_at', 'DESC');

        $query->withCount('likes');

        if ($userId) {
            $query->withExists(['likes as is_liked_by_viewer' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }]);
        }

        $paginatedResults = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page
        );

        // TODO: Implement include_replies
        // if ($parentOnly) {
        //     $query->whereNull('parent_comment_id');
        // }
        $domainComments = $paginatedResults->getCollection()->map(function ($persistenceComment) {
            return CommentMapper::mapToDomain($persistenceComment);
        });

        $paginatedResults->setCollection($domainComments);

        return Comments::fromEloquentPaginator($paginatedResults);
    }

    public function findPaginatedByEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        Pagination $pagination,
        bool $parentOnly = true
    ): object {
        $query = PersistenceComment::with(['user'])
            ->where('template_id', $entityType->getLegacyId())
            ->where('real_object_id', $entityId)
            ->orderBy('created_at', 'DESC');

        if ($parentOnly) {
            $query->whereNull('parent_comment_id');
        }

        return $query->paginate($pagination->per_page, ['*'], 'page', $pagination->page);
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        $comments = PersistenceComment::where('real_object_id', $entityId)->get();

        foreach ($comments as $comment) {
            Like::where('real_object_id', $comment->id)
                ->delete();

            $comment->delete();
        }
    }

    public function findByEntityWithPagination(EntityId $entityUid, string $entityType, int $page, int $perPage): array
    {
        $entityTemplateId = $this->getTemplateIdForEntityType($entityType);
        $commentTemplateId = $this->getTemplateIdForEntityType('comment');

        // Get paginated comments with user data
        $offset = ($page - 1) * $perPage;
        $comments = PersistenceComment::with('user')
            ->forEntity($entityTemplateId, $entityUid->value())
            ->whereNull('parent_comment_id') // Only top-level comments for now
            ->orderBy('created_at', 'DESC')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        if ($comments->isEmpty()) {
            return $this->emptyResult($page, $perPage);
        }

        // Batch load like counts
        $commentIds = $comments->pluck('id')->toArray();
        $likeCounts = $this->batchLoadLikeCounts($commentIds, $commentTemplateId);

        $commentData = $comments->map(function ($comment) use ($likeCounts) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->toISOString(),
                'updated_at' => $comment->updated_at->toISOString(),
                'author' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
                'likes_count' => $likeCounts[$comment->id] ?? 0,
                'is_reply' => $comment->parent_comment_id !== null,
            ];
        })->toArray();

        // Return structured data with pagination metadata
        return [
            'data' => $commentData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $this->countCommentsForEntity($entityTemplateId, $entityUid->value()),
                'has_more' => count($commentData) === $perPage
            ]
        ];
    }

    /**
     * Efficiently batch load like counts to avoid N+1 query problems
     *
     * This method demonstrates an important performance optimization
     * technique. Instead of querying for like counts individually for each
     * comment, we load all like counts in a single query and organize them
     * by comment ID for quick lookup
     */
    private function batchLoadLikeCounts(array $commentIds, int $commentTemplateId): array
    {
        return DB::table('likes')
            ->where('template_id', $commentTemplateId)
            ->whereIn('real_object_id', $commentIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();
    }

    /**
     * Resolve entity type strings to template IDs with caching
     *
     * This method handles the complexity of your template system by
     * maintaining a static cache of template ID lookups. This avoids
     * repeated database queries for the same entity types within a request
     */
    private function getTemplateIdForEntityType(string $entityType): int
    {
        static $templateCache = [];

        if (!isset($templateCache[$entityType])) {
            $template = ObjectTemplate::where('title', $entityType)->first();
            if (!$template) {
                throw new \InvalidArgumentException("Unknown entity type: {$entityType}");
            }
            $templateCache[$entityType] = $template->id;
        }

        return $templateCache[$entityType];
    }

    /**
     * Count total comments for pagination metadata
     */
    private function countCommentsForEntity(int $entityTemplateId, int $entityId): int
    {
        return PersistenceComment::where('template_id', $entityTemplateId)
            ->where('real_object_id', $entityId)
            ->whereNull('parent_comment_id')
            ->count();
    }

    /**
     * Return consistent empty result structure
     */
    private function emptyResult(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'has_more' => false
            ]
        ];
    }

    public function save(DomainComment $comment): DomainComment
    {
        $persistenceData = CommentMapper::mapToEntity($comment);

        $persistenceComment = PersistenceComment::updateOrCreate(
            ['id' => $persistenceData['id']],
            $persistenceData
        );

        return CommentMapper::mapToDomain($persistenceComment->fresh(['user']));
    }

    public function findById(EntityId $commentId): ?DomainComment
    {
        $entity = PersistenceComment::with('user')->find($commentId->value());

        return $entity ? CommentMapper::mapToDomain($entity) : null;
    }

    public function findAllByFilter(CommentFilterDTO $filter): array
    {
        return PersistenceComment::where('template_id', $filter->objectType->getLegacyId())
            ->where('real_object_id', $filter->entityId)
            ->get()
            ->map(function ($comment) {
                return CommentMapper::mapToDomain($comment);
            })
            ->toArray();
    }
}
