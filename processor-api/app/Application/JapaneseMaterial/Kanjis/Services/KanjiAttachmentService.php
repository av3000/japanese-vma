<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObjects\EntityId;
// use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Shared\Results\Result;
use App\Domain\Articles\Errors\ArticleErrors;

class KanjiAttachmentService
{
    public function __construct(
        private readonly KanjiRepositoryInterface $kanjiRepository,
        private readonly ArticleRepositoryInterface $articleRepository
    ) {}

    /**
     * Finds existing Kanjis by character and attaches/syncs them to an Article.
     *
     * @param EntityId $articleUuid The UUID of the article.
     * @param string[] $uniqueKanjiCharacters An array of unique Kanji character strings (e.g., ['亜', '愛']) from request input.
     * @return Result Success data: array of attached Kanji IDs, Failure data: Error.
     */
    public function attachKanjisToArticle(EntityId $articleUuid, array $uniqueKanjiCharacters): Result
    {
        if (empty($uniqueKanjiCharacters)) {
            return Result::success([]);
        }

        $articleId = $this->articleRepository->getIdByUuid($articleUuid);
        if (!$articleId) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        }

        $foundDomainKanjis = $this->kanjiRepository->findManyByCharacters(
            array_map(fn($char) => new KanjiCharacter($char), $uniqueKanjiCharacters)
        );

        $kanjiIdsToAttach = [];
        foreach ($foundDomainKanjis as $domainKanji) {
            $kanjiIdsToAttach[] = $domainKanji->getIdValue();
        }

        if (empty($kanjiIdsToAttach)) {
            return Result::success([]);
        }

        $this->articleRepository->syncKanjis($articleId, $kanjiIdsToAttach);

        return Result::success($kanjiIdsToAttach);
    }
}
