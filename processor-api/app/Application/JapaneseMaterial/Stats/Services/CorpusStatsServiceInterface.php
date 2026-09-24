<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Stats\Services;

use App\Shared\Results\Result;

interface CorpusStatsServiceInterface
{
    /**
     * @return Result success data is a CorpusStatsDTO
     */
    public function get(): Result;
}
