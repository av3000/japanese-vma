<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\Articles\Actions\Processing\CalculateJlptLevelsAction;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class KanjiAttachmentService
{
    public function __construct(
        private readonly KanjiRepositoryInterface $kanjiRepository,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly CalculateJlptLevelsAction $calculateJlptLevels,
    ) {
    }

    /**
     * Resolves characters to dictionary kanji and makes the article's attachments and JLPT
     * counters reflect exactly that set.
     *
     * Content-replacing on purpose (#250): an empty character list clears the pivot and zeroes
     * the counters, otherwise editing every kanji out of an article would leave stale data.
     * The pivot sync and the counter write happen in one transaction inside the repository.
     *
     * @param string[] $uniqueKanjiCharacters unique kanji characters, e.g. ['亜', '愛']
     *
     * @return Result Success data: int[] attached kanji ids (possibly empty). Failure: ResultError.
     */
    public function attachKanjisToArticle(EntityId $articleUuid, array $uniqueKanjiCharacters): Result
    {
        $articleId = $this->articleRepository->getIdByUuid($articleUuid);
        if (! $articleId) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        }

        /** @var Kanji[] $kanjis */
        $kanjis = $uniqueKanjiCharacters === []
            ? []
            : $this->kanjiRepository->findManyByCharacters(
                array_map(fn (string $char) => new KanjiCharacter($char), $uniqueKanjiCharacters)
            );

        $kanjiIds = array_map(fn (Kanji $kanji) => $kanji->getIdValue(), $kanjis);

        $this->articleRepository->syncKanjis($articleId, $kanjiIds, $this->calculateJlptLevels->execute($kanjis));

        return Result::success($kanjiIds);
    }
}
