<?php

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

readonly class ArticleCriteriaDTO implements ArticleIncludeOptionsInterface
{
    public function __construct(
        public ?ArticleSortCriteria $sort,
        public ?SearchTerm $search = null,
        public ?int $categoryId = null,
        public ?string $authorUid = null,
        public array $visibilityRules = [],
        public ?Pagination $pagination = null,
        public bool $include_kanjis = false,
        public bool $include_words = false,
        public ?int $kanjiId = null,
    ) {
    }

    public function includeKanjis(): bool
    {
        return $this->include_kanjis;
    }

    public function includeWords(): bool
    {
        return $this->include_kanjis;
    }
}
