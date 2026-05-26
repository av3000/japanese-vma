<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Radicals\Services;

use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Domain\JapaneseMaterial\Radicals\Errors\RadicalErrors;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

class RadicalService implements RadicalServiceInterface
{
    public function __construct(
        private readonly RadicalRepositoryInterface $radicalRepository,
    ) {}

    public function find(RadicalQueryCriteria $criteria): Result
    {
        return Result::success($this->radicalRepository->find($criteria));
    }

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result
    {
        if (EntityId::isValid($identifier)) {
            $radical = $this->radicalRepository->findByUuid(EntityId::from($identifier), $withKanjis);

            return $radical
                ? Result::success($radical)
                : Result::failure(RadicalErrors::notFound($identifier));
        }

        if (ctype_digit($identifier) && (int) $identifier > 0) {
            $radical = $this->radicalRepository->findByLegacyId((int) $identifier, $withKanjis);

            return $radical
                ? Result::success($radical)
                : Result::failure(RadicalErrors::notFound($identifier));
        }

        return Result::failure(RadicalErrors::invalidIdentifier());
    }
}
