<?php

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\ValueObjects\{ArticleSearchTerm, ArticleSortCriteria};
use App\Domain\Shared\ValueObjects\Pagination;

readonly class ArticleCriteriaDTO
{
    public function __construct(
        public ?ArticleSearchTerm $search = null,
        public ?int $categoryId = null,
        public ?ArticleSortCriteria $sort,
        public array $visibilityRules = [],
        public ?Pagination $pagination = null
    ) {}
}
