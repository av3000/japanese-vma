<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\JapaneseMaterial\Sentences\Errors\SentenceErrors;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class SentenceService implements SentenceServiceInterface
{
    public function __construct(
        private readonly SentenceRepositoryInterface $sentenceRepository,
    ) {}

    public function find(SentenceQueryCriteria $criteria): Result
    {
        return Result::success($this->sentenceRepository->find($criteria));
    }

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result
    {
        if (EntityId::isValid($identifier)) {
            $sentence = $this->sentenceRepository->findByUuid(EntityId::from($identifier), $withKanjis);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        if (ctype_digit($identifier) && (int) $identifier > 0) {
            $sentence = $this->sentenceRepository->findByLegacyId((int) $identifier, $withKanjis);

            return $sentence
                ? Result::success($sentence)
                : Result::failure(SentenceErrors::notFound($identifier));
        }

        return Result::failure(SentenceErrors::invalidIdentifier());
    }
}
