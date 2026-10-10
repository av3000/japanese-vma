<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\ValueObjects;

use InvalidArgumentException;

/**
 * The limits and filters one Import Run applies to one Content Source.
 */
final readonly class ImportRunSettings
{
    /**
     * @param int $maxCreatedPerRun Imported Articles one run may create
     * @param int $maxListed Listed articles one run may look at, filtered or not
     * @param int $minLeadLength Leads shorter than this (characters) are one-line bulletins
     * @param list<string> $excludedGenres An article is skipped only when every genre is listed here
     * @param int $stalledAfterRuns Warn after this many successful runs in a row created nothing; 0 turns it off
     */
    public function __construct(
        public int $maxCreatedPerRun,
        public int $maxListed,
        public int $minLeadLength,
        public array $excludedGenres,
        public int $stalledAfterRuns,
    ) {
        if ($maxCreatedPerRun < 0 || $maxListed < 0 || $minLeadLength < 0 || $stalledAfterRuns < 0) {
            throw new InvalidArgumentException('Import run limits cannot be negative');
        }
    }

    public function isLimitReached(int $created, int $listed): bool
    {
        return $created >= $this->maxCreatedPerRun || $listed >= $this->maxListed;
    }

    /**
     * Excluded only when every genre is excluded: an AI story filed under both 気象・災害 and
     * 科学・文化 is still worth reading, a bare weather bulletin is not.
     *
     * @param list<string> $genres
     */
    public function excludesEveryGenre(array $genres): bool
    {
        return $genres !== [] && array_diff($genres, $this->excludedGenres) === [];
    }

    public function isLeadTooShort(string $lead): bool
    {
        return mb_strlen(trim($lead)) < $this->minLeadLength;
    }
}
