<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Radicals\Queries;

use App\Domain\Shared\ValueObjects\Pagination;

final readonly class RadicalQueryCriteria
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        public ?string $keyword,
        public ?string $radical,
        public ?string $meaning,
        public ?string $hiragana,
        public ?int $strokes,
        public Pagination $pagination,
        /** Radicals saved in one catalogue, the list behind the catalogue detail page (#347). */
        public ?int $catalogueId = null,
    ) {
    }

    public static function forListing(
        int $page,
        int $perPage,
        ?string $keyword,
        ?string $radical,
        ?string $meaning,
        ?string $hiragana,
        ?int $strokes,
        ?int $catalogueId = null,
    ): self {
        return new self(
            keyword: $keyword,
            radical: $radical,
            meaning: $meaning,
            hiragana: $hiragana,
            strokes: $strokes,
            pagination: new Pagination(page: $page, per_page: $perPage),
            catalogueId: $catalogueId,
        );
    }
}
