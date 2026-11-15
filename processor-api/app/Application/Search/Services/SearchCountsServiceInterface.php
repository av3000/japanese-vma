<?php

declare(strict_types=1);

namespace App\Application\Search\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Shared\Results\Result;

/**
 * Per-scope hit counts for the Header search.
 */
interface SearchCountsServiceInterface
{
    /**
     * Each count equals what that scope's list reports as its total for the same keyword
     * and actor, or null where that list rejects the keyword.
     *
     * @param string $keyword already trimmed, at least one character
     *
     * @return Result Success data: SearchCountsDTO
     */
    public function count(string $keyword, ?AuthenticatedUser $actor = null): Result;
}
