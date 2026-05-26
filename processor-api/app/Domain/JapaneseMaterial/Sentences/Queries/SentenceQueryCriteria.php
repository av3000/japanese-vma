<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Queries;

use App\Domain\Shared\ValueObjects\Pagination;

final readonly class SentenceQueryCriteria
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        public Pagination $pagination,
        public ?string $keyword = null,
        public ?string $content = null,
        public ?string $tatoebaEntry = null,
        public ?int $userId = null,
    ) {}

    public static function forListing(
        int $page = Pagination::MIN_PAGE,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $keyword = null,
        ?string $content = null,
        ?string $tatoebaEntry = null,
        ?int $userId = null,
    ): self {
        return new self(
            pagination: new Pagination($page, $perPage),
            keyword: $keyword,
            content: $content,
            tatoebaEntry: $tatoebaEntry,
            userId: $userId,
        );
    }
}
