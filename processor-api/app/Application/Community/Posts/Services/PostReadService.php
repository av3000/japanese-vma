<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Community\Posts\DTOs\PostDetailResultDTO;
use App\Domain\Community\Posts\DTOs\PostListItemDTO;
use App\Domain\Community\Posts\DTOs\PostListResultDTO;
use App\Domain\Community\Posts\Errors\PostErrors;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Community\Posts\Models\PostStats;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostReadService implements PostReadServiceInterface
{
    /**
     * The legacy list card renders at most three tags
     * (PostController::getPostImpressionsSearch()).
     */
    private const LIST_HASHTAG_LIMIT = 3;

    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly LoadEntityStatsAction $loadEntityStats,
        private readonly HashtagServiceInterface $hashtagService,
        private readonly IncrementViewAction $incrementView,
    ) {
    }

    public function find(PostQueryCriteria $criteria): Result
    {
        $page = $this->postRepository->find($criteria);

        if ($page->items === []) {
            return Result::success(new PostListResultDTO([], $page->pagination));
        }

        $postIds = array_map(static fn (Post $post): int => $post->getIdValue(), $page->items);

        // Bounded enrichment: one stats call and one hashtag call per page,
        // regardless of page size. LoadEntityStatsAction declares the template id
        // as a string, so cast it here rather than relying on coercion.
        $statsById = $this->loadEntityStats->batchLoadStatsById(
            (string) ObjectTemplateType::POST->getLegacyId(),
            $postIds,
        );
        $hashtagsById = $this->hashtagService->getBatchHashtags($postIds, ObjectTemplateType::POST);

        $items = array_map(
            fn (Post $post): PostListItemDTO => new PostListItemDTO(
                post: $post,
                stats: $this->statsFor($statsById, $post->getIdValue()),
                hashtags: array_slice(
                    array_values($hashtagsById[$post->getIdValue()] ?? []),
                    0,
                    self::LIST_HASHTAG_LIMIT,
                ),
            ),
            $page->items,
        );

        return Result::success(new PostListResultDTO($items, $page->pagination));
    }

    public function findByIdentifier(string $identifier, Viewer $viewer): Result
    {
        if (EntityId::isValid($identifier)) {
            $post = $this->postRepository->findByUuid(EntityId::from($identifier));
        } elseif (ctype_digit($identifier) && (int) $identifier > 0) {
            $post = $this->postRepository->findByLegacyId((int) $identifier);
        } else {
            return Result::failure(PostErrors::invalidIdentifier());
        }

        if ($post === null) {
            return Result::failure(PostErrors::notFound($identifier));
        }

        // Recorded before stats are read so the viewer's own view is counted,
        // matching the legacy detail response.
        $this->recordView($post->getIdValue(), $viewer);

        $statsById = $this->loadEntityStats->batchLoadStatsById(
            (string) ObjectTemplateType::POST->getLegacyId(),
            [$post->getIdValue()],
        );

        return Result::success(new PostDetailResultDTO(
            post: $post,
            stats: $this->statsFor($statsById, $post->getIdValue()),
            hashtags: $this->hashtagService->getHashtags($post->getIdValue(), ObjectTemplateType::POST),
        ));
    }

    /**
     * Legacy Post detail counts views for authenticated users only
     * (PostController::incrementView()). Failures must never fail the read.
     */
    private function recordView(int $postId, Viewer $viewer): void
    {
        if (! $viewer->isAuthenticated()) {
            return;
        }

        try {
            $this->incrementView->execute($postId, ObjectTemplateType::POST, $viewer);
        } catch (Throwable $exception) {
            Log::error('Failed to increment post view', [
                'post_id' => $postId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param array<int, array{likes: int, downloads: int, views: int, comments: int}> $statsById
     */
    private function statsFor(array $statsById, int $postId): PostStats
    {
        $stats = $statsById[$postId] ?? [];

        return new PostStats(
            likesCount: (int) ($stats['likes'] ?? 0),
            viewsCount: (int) ($stats['views'] ?? 0),
            commentsCount: (int) ($stats['comments'] ?? 0),
        );
    }
}
