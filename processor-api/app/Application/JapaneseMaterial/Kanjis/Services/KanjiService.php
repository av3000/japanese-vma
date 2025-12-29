<?php

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiServiceInterface;
use App\Domain\JapaneseMaterial\Kanjis\Errors\KanjiErrors;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanjis;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class KanjiService implements KanjiServiceInterface
{
    public function __construct(
        private readonly KanjiRepositoryInterface $kanjiRepository
    ) {}

    public function findByUuid(EntityId $uuid): Result
    {
        $kanji = $this->kanjiRepository->findByUuid($uuid);

        if (!$kanji) {
            return Result::failure(KanjiErrors::notFound($uuid->value()));
        }

        return Result::success($kanji);
    }

    public function findByCharacter(KanjiCharacter $character): Result
    {
        $kanji = $this->kanjiRepository->findByCharacter($character);

        if (!$kanji) {
            return Result::failure(KanjiErrors::notFound($character->value()));
        }

        return Result::success($kanji);
    }

    public function find(?KanjiQueryCriteria $criteria = null): Result
    {
        /** @var Kanjis $paginatedKanjisCollection */
        $paginatedKanjisCollection = $this->kanjiRepository->find($criteria);

        return Result::success($paginatedKanjisCollection->getPaginator());
    }
}
