<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Queries;

use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\ValueObjects\Pagination;

final readonly class PostQueryCriteria
{
    /**
     * 5 preserves the legacy visible page size (PostController::index/generateQuery).
     */
    public const DEFAULT_PER_PAGE = 5;

    public const MAX_PER_PAGE = 50;

    public function __construct(
        public Pagination $pagination,
        public ?string $keyword = null,
        public ?string $hashtag = null,
        public ?PostTopic $topic = null,
        public PostSort $sort = PostSort::DEFAULT,
    ) {
    }

    public static function forListing(
        int $page = Pagination::MIN_PAGE,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $keyword = null,
        ?string $hashtag = null,
        ?PostTopic $topic = null,
        PostSort $sort = PostSort::DEFAULT,
    ): self {
        return new self(
            pagination: new Pagination($page, $perPage),
            keyword: self::normalizeKeyword($keyword),
            hashtag: self::normalizeHashtag($hashtag),
            topic: $topic,
            sort: $sort,
        );
    }

    /**
     * Accepts a tag with or without a leading `#` and normalizes it to exactly
     * one, matching how legacy tags are persisted in `uniquehashtags.content`.
     */
    public static function normalizeHashtag(?string $hashtag): ?string
    {
        if ($hashtag === null) {
            return null;
        }

        $trimmed = ltrim(trim($hashtag), '#');

        return $trimmed === '' ? null : '#'.$trimmed;
    }

    private static function normalizeKeyword(?string $keyword): ?string
    {
        if ($keyword === null) {
            return null;
        }

        $trimmed = trim($keyword);

        return $trimmed === '' ? null : $trimmed;
    }
}
