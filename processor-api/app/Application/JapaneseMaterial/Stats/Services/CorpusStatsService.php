<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Stats\Services;

use App\Application\JapaneseMaterial\Stats\Interfaces\Readers\CorpusStatsReaderInterface;
use App\Shared\Results\Result;

class CorpusStatsService implements CorpusStatsServiceInterface
{
    public function __construct(
        private readonly CorpusStatsReaderInterface $reader,
    ) {
    }

    public function get(): Result
    {
        return Result::success($this->reader->read());
    }
}
