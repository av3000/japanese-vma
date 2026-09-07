<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleDateRange;
use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

/**
 * Normalized user intent for an Article list request.
 *
 * This is the "AND user intent" half of the list invariant. It carries no
 * visibility information: eligibility is derived from the actor by
 * ArticlePolicy::scopeFor(), so no query parameter can widen access.
 *
 * Everything here is canonical. The temporary `search`, `category`, `sort_by` and
 * `sort_dir` aliases are translated at the HTTP edge, so application and
 * persistence code never sees them.
 *
 * Multi-value dimensions are OR within a dimension and AND across dimensions.
 */
final readonly class ArticleListQuery
{
    /**
     * @param array<int, ArticleJlptLevel> $jlptLevels
     * @param array<int, int> $hashtagIds uniquehashtags.id values
     * @param array<int, int> $kanjiIds
     * @param array<int, int> $wordIds
     */
    public function __construct(
        public ArticleListSort $sort,
        public Pagination $pagination,
        public ?SearchTerm $search = null,
        public array $jlptLevels = [],
        public array $hashtagIds = [],
        public ?string $authorUid = null,
        public array $kanjiIds = [],
        public array $wordIds = [],
        public ?ArticleDateRange $createdBetween = null,
    ) {
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search->value !== '';
    }

    /**
     * The canonical echo of user intent, for the AFM-05 `query` response field.
     * Never includes visibility, and never a compatibility alias.
     *
     * @return array{q: ?string, filters: array<string, mixed>, sort: string}
     */
    public function toCanonicalArray(): array
    {
        $filters = [];

        if ($this->jlptLevels !== []) {
            $filters['jlpt_levels'] = array_map(
                static fn (ArticleJlptLevel $level): string => $level->value,
                $this->jlptLevels,
            );
        }

        if ($this->hashtagIds !== []) {
            $filters['hashtag_ids'] = $this->hashtagIds;
        }

        if ($this->kanjiIds !== []) {
            $filters['kanji_ids'] = $this->kanjiIds;
        }

        if ($this->wordIds !== []) {
            $filters['word_ids'] = $this->wordIds;
        }

        if ($this->authorUid !== null) {
            $filters['author_uid'] = $this->authorUid;
        }

        return [
            'q' => $this->hasSearch() ? $this->search->value : null,
            'filters' => $filters,
            'sort' => $this->sort->toSigned(),
        ];
    }
}
