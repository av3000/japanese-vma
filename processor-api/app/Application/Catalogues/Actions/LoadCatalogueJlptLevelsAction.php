<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Actions;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Domain\Catalogues\Enums\CatalogueJlptSource;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\Shared\ValueObjects\JlptLevels;

/**
 * JLPT counts for a page of catalogues, computed from their items on read (#386).
 *
 * One grouped query per source present on the page, never one per catalogue: a page of Kanji
 * and Words catalogues costs two queries whatever its size. What each type counts is
 * CatalogueJlptSource's rule.
 *
 * As in CalculateJlptLevelsAction, an entry with no level counts as `uncommon`, and so does any
 * value outside N1–N5. Word levels are matched with or without an `N` prefix, because the word
 * bank's format is not settled: every word is unassigned (`-`) today.
 */
final class LoadCatalogueJlptLevelsAction
{
    private const LEVELS = [
        JlptLevel::N1 => 'n1',
        JlptLevel::N2 => 'n2',
        JlptLevel::N3 => 'n3',
        JlptLevel::N4 => 'n4',
        JlptLevel::N5 => 'n5',
    ];

    public function __construct(
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
    ) {
    }

    /**
     * @param Catalogue[] $catalogues
     *
     * @return array<int, JlptLevels|null> map catalogue id => counts, null when its type has no JLPT data
     */
    public function execute(array $catalogues): array
    {
        $levelsById = [];
        $idsBySource = [];

        foreach ($catalogues as $catalogue) {
            $source = CatalogueJlptSource::forType($catalogue->getType());
            $levelsById[$catalogue->getIdValue()] = $source === null ? null : JlptLevels::empty();

            if ($source !== null) {
                $idsBySource[$source->value][] = $catalogue->getIdValue();
            }
        }

        foreach ($idsBySource as $source => $catalogueIds) {
            $countsById = $this->catalogueItemRepository->countJlptLevelsByCatalogueIds(
                CatalogueJlptSource::from($source),
                $catalogueIds,
            );

            foreach ($countsById as $catalogueId => $countsByLevel) {
                $levelsById[$catalogueId] = $this->fold($countsByLevel);
            }
        }

        return $levelsById;
    }

    /**
     * @param array<string, int> $countsByLevel raw jlpt value => count
     */
    private function fold(array $countsByLevel): JlptLevels
    {
        $counts = array_fill_keys(self::LEVELS, 0);
        $uncommon = 0;

        foreach ($countsByLevel as $rawLevel => $count) {
            $level = ltrim(strtoupper(trim((string) $rawLevel)), 'N');

            if (isset(self::LEVELS[$level])) {
                $counts[self::LEVELS[$level]] += $count;
            } else {
                $uncommon += $count;
            }
        }

        return new JlptLevels(
            n1: $counts['n1'],
            n2: $counts['n2'],
            n3: $counts['n3'],
            n4: $counts['n4'],
            n5: $counts['n5'],
            uncommon: $uncommon,
        );
    }
}
