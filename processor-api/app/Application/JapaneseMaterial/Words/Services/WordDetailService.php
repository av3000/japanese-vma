<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailIncludes;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Shared\Results\Result;

final readonly class WordDetailService implements WordDetailServiceInterface
{
    private const RELATED_LIMIT = 5;

    public function __construct(
        private WordServiceInterface $wordService,
        private WordRepositoryInterface $wordRepository,
        private ArticleServiceInterface $articleService,
    ) {
    }

    public function findByIdentifier(
        string $identifier,
        WordDetailIncludes $includes,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result {
        $wordResult = $this->wordService->findByIdentifier($identifier);

        if ($wordResult->isFailure()) {
            return $wordResult;
        }

        /** @var Word $word */
        $word = $wordResult->getData();
        $wordId = $word->getIdValue();

        $kanjis = $includes->kanjis
            ? $this->wordRepository->findRelatedKanjis($wordId, self::RELATED_LIMIT)
            : null;

        $articles = $includes->articles
            ? $this->articleService->getArticlesList(new ArticleListDTO(
                category: null,
                search: null,
                author_uid: null,
                sort_by: 'id',
                sort_dir: 'asc',
                per_page: self::RELATED_LIMIT,
                page: 1,
                include_stats_counts: true,
                include_hashtags: true,
                include_kanjis: false,
                include_words: false,
                kanji_id: null,
                word_id: $wordId,
            ), $authenticatedUser)->items
            : null;

        return Result::success(new WordDetailResultDTO(
            word: $word,
            kanjis: $kanjis,
            articles: $articles,
        ));
    }
}
