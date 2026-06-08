<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\Queries;

use App\Domain\Shared\ValueObjects\Pagination;

final readonly class WordQueryCriteria
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        public Pagination $pagination,
        public ?string $keyword = null,
        public ?string $word = null,
        public ?string $furigana = null,
        public ?string $jlpt = null,
    ) {
    }

    public static function forListing(
        int $page = Pagination::MIN_PAGE,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $keyword = null,
        ?string $word = null,
        ?string $furigana = null,
        ?string $jlpt = null,
    ): self {
        return new self(
            pagination: new Pagination($page, $perPage),
            keyword: $keyword,
            word: $word,
            furigana: $furigana,
            jlpt: $jlpt,
        );
    }
}
