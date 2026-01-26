<?php

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Domain\Shared\ValueObjects\Pagination;

readonly class ArticleCriteriaDTO implements ArticleIncludeOptionsInterface
{
    public function __construct(
        public ?SearchTerm $search = null,
        public ?int $categoryId = null,
        public ?ArticleSortCriteria $sort,
        public array $visibilityRules = [],
        public ?Pagination $pagination = null,
        public bool $include_kanjis = false
    ) {}

    public function includeKanjis(): bool
    {
        return $this->include_kanjis;
    }
}
