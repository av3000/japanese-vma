<?php

declare(strict_types=1);

namespace App\Application\Search\Services;

use App\Application\Articles\Services\ArticleListServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Application\JapaneseMaterial\Radicals\Services\RadicalServiceInterface;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Application\Search\Interfaces\Caches\SearchCountCacheInterface;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Search\DTOs\SearchCountsDTO;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Shared\Results\Result;
use Closure;

/**
 * Asks each list module for its own count, through the same filters its list uses, so a
 * count cannot drift from the total that list then shows.
 *
 * Dictionary counts are the same for every viewer and change only on import, so they are
 * cached for longer. Article counts depend on who is asking: only the guest count is shared,
 * and only briefly, so a new public article shows up within a minute.
 */
final readonly class SearchCountsService implements SearchCountsServiceInterface
{
    public const DICTIONARY_TTL_SECONDS = 600;

    public const GUEST_ARTICLES_TTL_SECONDS = 60;

    public function __construct(
        private ArticleListServiceInterface $articleListService,
        private KanjiServiceInterface $kanjiService,
        private WordServiceInterface $wordService,
        private SentenceServiceInterface $sentenceService,
        private RadicalServiceInterface $radicalService,
        private SearchCountCacheInterface $cache,
    ) {
    }

    public function count(string $keyword, ?AuthenticatedUser $actor = null): Result
    {
        $counts = [];

        foreach ($this->scopeCounters($keyword, $actor) as $scope => $counter) {
            $result = $counter();

            if ($result->isFailure()) {
                return $result;
            }

            $counts[$scope] = $result->getData();
        }

        return Result::success(new SearchCountsDTO(
            q: $keyword,
            articles: $counts['articles'],
            kanji: $counts['kanji'],
            words: $counts['words'],
            sentences: $counts['sentences'],
            radicals: $counts['radicals'],
        ));
    }

    /**
     * @return array<string, Closure(): Result>
     */
    private function scopeCounters(string $keyword, ?AuthenticatedUser $actor): array
    {
        return [
            'articles' => fn (): Result => $this->countArticles($keyword, $actor),
            'kanji' => fn (): Result => $this->dictionaryCount(
                'kanji',
                $keyword,
                KanjiQueryCriteria::KEYWORD_MAX_LENGTH,
                fn (): Result => $this->kanjiService->count(KanjiQueryCriteria::forListing(keyword: $keyword)),
            ),
            'words' => fn (): Result => $this->dictionaryCount(
                'words',
                $keyword,
                WordQueryCriteria::KEYWORD_MAX_LENGTH,
                fn (): Result => $this->wordService->count(WordQueryCriteria::forListing(keyword: $keyword)),
            ),
            'sentences' => fn (): Result => $this->dictionaryCount(
                'sentences',
                $keyword,
                SentenceQueryCriteria::KEYWORD_MAX_LENGTH,
                fn (): Result => $this->sentenceService->count(SentenceQueryCriteria::forListing(keyword: $keyword)),
            ),
            'radicals' => fn (): Result => $this->dictionaryCount(
                'radicals',
                $keyword,
                RadicalQueryCriteria::KEYWORD_MAX_LENGTH,
                fn (): Result => $this->radicalService->count(RadicalQueryCriteria::forListing(
                    page: Pagination::MIN_PAGE,
                    perPage: Pagination::DEFAULT_PER_PAGE,
                    keyword: $keyword,
                    radical: null,
                    meaning: null,
                    hiragana: null,
                    strokes: null,
                )),
            ),
        ];
    }

    /**
     * The article list takes at least two characters (its request rule) and at most
     * SearchTerm's limit, which is measured in bytes; SearchTerm decides, exactly as it
     * does for the list.
     *
     * @return Result Success data: int|null
     */
    private function countArticles(string $keyword, ?AuthenticatedUser $actor): Result
    {
        // Mirrors IndexArticleRequest's `min:` rule on `q`, which counts characters; keep the two in step.
        if (mb_strlen($keyword) < SearchTerm::MIN_LENGTH) {
            return Result::success(null);
        }

        try {
            $search = SearchTerm::fromInput($keyword);
        } catch (ValueObjectValidationException) {
            return Result::success(null);
        }

        $criteria = new ArticleQueryCriteria(
            sort: ArticleSortCriteria::default(),
            pagination: new Pagination(Pagination::MIN_PAGE, Pagination::DEFAULT_PER_PAGE),
            search: $search,
        );
        $count = fn (): Result => $this->articleListService->count($criteria, $actor);

        return $actor === null
            ? $this->cached('articles', $keyword, self::GUEST_ARTICLES_TTL_SECONDS, $count)
            : $count();
    }

    /**
     * @param Closure(): Result $count
     *
     * @return Result Success data: int|null
     */
    private function dictionaryCount(string $scope, string $keyword, int $maxLength, Closure $count): Result
    {
        if (mb_strlen($keyword) > $maxLength) {
            return Result::success(null);
        }

        return $this->cached($scope, $keyword, self::DICTIONARY_TTL_SECONDS, $count);
    }

    /**
     * @param Closure(): Result $count
     *
     * @return Result Success data: int
     */
    private function cached(string $scope, string $keyword, int $ttlSeconds, Closure $count): Result
    {
        $stored = $this->cache->get($scope, $keyword);

        if ($stored !== null) {
            return Result::success($stored);
        }

        $result = $count();

        if ($result->isSuccess()) {
            $this->cache->put($scope, $keyword, (int) $result->getData(), $ttlSeconds);
        }

        return $result;
    }
}
