<?php
namespace App\Domain\Articles\DTOs;

use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Domain\Shared\ValueObjects\SortCriteria;
use App\Domain\Shared\ValueObjects\PerPageLimit;

class ArticleListDTO
{
    public function __construct(
        public ?int $category,
        public ?SearchTerm $search,
        public SortCriteria $sort,
        public PerPageLimit $perPage
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            category: $validated['category'] ?? null,
            search: isset($validated['search'])
                ? SearchTerm::fromInput($validated['search'])
                : null,
            sort: SortCriteria::fromInputOrDefault(
                $validated['sort_by'] ?? null,
                $validated['sort_dir'] ?? null
            ),
            perPage: PerPageLimit::fromInputOrDefault($validated['per_page'] ?? null)
        );
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
