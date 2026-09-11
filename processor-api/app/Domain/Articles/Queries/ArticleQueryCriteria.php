<?php

declare(strict_types=1);

namespace App\Domain\Articles\Queries;

use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleDateRange;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;

/**
 * Normalized user intent for an Article list request.
 *
 * This is the "AND user intent" half of the list invariant. It carries no
 * visibility information: eligibility is derived from the actor by
 * ArticlePolicy::scopeFor(), so no query parameter can widen access.
 *
 * Multi-value dimensions are OR within a dimension and AND across dimensions.
 */
final readonly class ArticleQueryCriteria
{
    /**
     * @param array<int, ArticleJlptLevel> $jlptLevels
     * @param array<int, int> $hashtagIds uniquehashtags.id values
     * @param array<int, int> $kanjiIds
     * @param array<int, int> $wordIds
     */
    public function __construct(
        public ArticleSortCriteria $sort,
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

    /**
     * The HTTP edge. Expects IndexArticleRequest::validated(): every value has
     * already passed the closed-vocabulary rules, so nothing here can throw for a
     * request the FormRequest accepted.
     *
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            sort: ArticleSortCriteria::fromSignedOrDefault($validated['sort'] ?? null),
            pagination: Pagination::fromInputOrDefault(
                isset($validated['page']) ? (int) $validated['page'] : null,
                isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            ),
            search: isset($validated['q']) ? SearchTerm::fromInputOrNull((string) $validated['q']) : null,
            jlptLevels: array_map(
                static fn (string $level): ArticleJlptLevel => ArticleJlptLevel::from($level),
                $validated['jlpt_levels'] ?? [],
            ),
            hashtagIds: array_map('intval', $validated['hashtag_ids'] ?? []),
            authorUid: $validated['author_uid'] ?? null,
            kanjiIds: array_map('intval', $validated['kanji_ids'] ?? []),
            wordIds: array_map('intval', $validated['word_ids'] ?? []),
            createdBetween: ArticleDateRange::fromInput(
                $validated['created_from'] ?? null,
                $validated['created_to'] ?? null,
            ),
        );
    }

    /**
     * Internal callers, e.g. related-Article panels on kanji and word detail.
     *
     * @param array<int, int> $kanjiIds
     * @param array<int, int> $wordIds
     * @param array<int, int> $hashtagIds
     */
    public static function forListing(
        int $page = Pagination::MIN_PAGE,
        int $perPage = Pagination::DEFAULT_PER_PAGE,
        ?ArticleSortCriteria $sort = null,
        array $kanjiIds = [],
        array $wordIds = [],
        array $hashtagIds = [],
        ?string $authorUid = null,
    ): self {
        return new self(
            sort: $sort ?? ArticleSortCriteria::default(),
            pagination: new Pagination($page, $perPage),
            hashtagIds: $hashtagIds,
            authorUid: $authorUid,
            kanjiIds: $kanjiIds,
            wordIds: $wordIds,
        );
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search->value !== '';
    }
}
