<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Domain\JapaneseMaterial\Words\Errors\WordErrors;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class WordService implements WordServiceInterface
{
    public function __construct(
        private readonly WordRepositoryInterface $wordRepository,
    ) {
    }

    public function find(WordQueryCriteria $criteria): Result
    {
        return Result::success($this->wordRepository->find($criteria));
    }

    public function findByIdentifier(string $identifier): Result
    {
        $identifier = trim($identifier);

        if ($identifier === '' || ctype_digit($identifier)) {
            return Result::failure(WordErrors::invalidIdentifier());
        }

        if (EntityId::isValid($identifier)) {
            $word = $this->wordRepository->findByUuid(EntityId::from($identifier));

            return $word
                ? Result::success($word)
                : Result::failure(WordErrors::notFound($identifier));
        }

        $word = $this->wordRepository->findBySurface($identifier);

        return $word
            ? Result::success($word)
            : Result::failure(WordErrors::notFound($identifier));
    }
}
