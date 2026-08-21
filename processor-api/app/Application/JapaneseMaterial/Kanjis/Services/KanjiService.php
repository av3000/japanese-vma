<?php

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Domain\JapaneseMaterial\Kanjis\Errors\KanjiErrors;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use App\Shared\Results\ResultError;

class KanjiService implements KanjiServiceInterface
{
    public function __construct(
        private readonly KanjiRepositoryInterface $kanjiRepository
    ) {
    }

    public function findByIdentifier(string $identifier): Result
    {
        $identifier = trim(rawurldecode($identifier));

        if (EntityId::isValid($identifier)) {
            return $this->resultForKanji(
                $this->kanjiRepository->findByUuid(EntityId::from($identifier)),
                $identifier,
            );
        }

        if (ctype_digit($identifier) && (int) $identifier > 0) {
            return $this->resultForKanji(
                $this->kanjiRepository->findById((int) $identifier),
                $identifier,
            );
        }

        if (preg_match('/^\p{Han}$/u', $identifier) === 1) {
            return $this->resultForKanji(
                $this->kanjiRepository->findByCharacter(new KanjiCharacter($identifier)),
                $identifier,
            );
        }

        return Result::failure(new ResultError(
            'INVALID_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID, positive numeric Kanji ID, or a single Kanji character.',
        ));
    }

    public function findByUuid(EntityId $uuid): Result
    {
        $kanji = $this->kanjiRepository->findByUuid($uuid);

        if (! $kanji) {
            return Result::failure(KanjiErrors::notFound($uuid->value()));
        }

        return Result::success($kanji);
    }

    public function findByCharacter(KanjiCharacter $character): Result
    {
        $kanji = $this->kanjiRepository->findByCharacter($character);

        if (! $kanji) {
            return Result::failure(KanjiErrors::notFound($character->value()));
        }

        return Result::success($kanji);
    }

    public function find(?KanjiQueryCriteria $criteria = null): Result
    {
        return Result::success($this->kanjiRepository->find($criteria));
    }

    private function resultForKanji(?DomainKanji $kanji, string $identifier): Result
    {
        return $kanji
            ? Result::success($kanji)
            : Result::failure(KanjiErrors::notFound($identifier));
    }
}
