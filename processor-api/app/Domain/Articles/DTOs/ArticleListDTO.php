<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\ValueObjects\ArticleSearchTerm;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\PerPageLimit;

class ArticleListDTO
{
    public function __construct(
        public ?int $category,
        public ?ArticleSearchTerm $search,
        public ArticleSortCriteria $sort,
        public PerPageLimit $perPage,
        public bool $includeStats = false
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            category: $validated['category'] ?? null,
            search: isset($validated['search'])
                ? ArticleSearchTerm::fromInput($validated['search'])
                : null,
            sort: ArticleSortCriteria::fromInputOrDefault(
                $validated['sort_by'] ?? null,
                $validated['sort_dir'] ?? null
            ),
            perPage: PerPageLimit::fromInputOrDefault($validated['per_page'] ?? null),
            includeStats: $validated['include_stats'] ?? false);
    }

    public function hasSearch(): bool
    {
        return $this->search !== null;
    }

    public function getSearchValue(): ?string
    {
        return $this->search?->value;
    }

    public function hasCategory(): bool
    {
        return $this->category !== null;
    }
}
