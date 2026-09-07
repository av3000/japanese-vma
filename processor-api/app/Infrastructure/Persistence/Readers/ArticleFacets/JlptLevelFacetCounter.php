<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers\ArticleFacets;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleFacetValueDTO;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\Enums\ArticleFacetDimension;
use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Infrastructure\Persistence\Readers\ArticleQueryScope;

/**
 * Counts eligible Articles per JLPT level.
 *
 * The six levels are fixed and every Article carries all six columns, so this is one
 * query with six conditional aggregates rather than six queries. That keeps the cost
 * independent of how many levels the user selected.
 */
final readonly class JlptLevelFacetCounter
{
    public function __construct(
        private ArticleQueryScope $queryScope,
    ) {
    }

    public function count(ArticleListQuery $query, ArticleVisibilityScope $scope): ArticleFacetDTO
    {
        // Disjunctive: every filter applies except the JLPT selection itself, so
        // choosing N5 does not collapse the list to just N5.
        $builder = $this->queryScope->newQuery($query, $scope, ArticleFacetDimension::JLPT_LEVELS);

        $selections = [];
        foreach (ArticleJlptLevel::cases() as $level) {
            $selections[] = "COUNT(*) FILTER (WHERE {$level->column()} > 0) AS {$level->value}_count";
        }

        /** @var object|null $row */
        $row = $builder->toBase()->selectRaw(implode(', ', $selections))->first();

        $selected = array_map(
            static fn (ArticleJlptLevel $level): string => $level->value,
            $query->jlptLevels,
        );

        $values = [];
        foreach (ArticleJlptLevel::displayOrder() as $level) {
            $key = $level->value;

            $values[] = new ArticleFacetValueDTO(
                key: $key,
                label: $level->label(),
                count: (int) ($row?->{$key.'_count'} ?? 0),
                selected: in_array($key, $selected, true),
            );
        }

        return ArticleFacetDTO::multi(
            ArticleFacetDimension::JLPT_LEVELS->value,
            ArticleFacetDimension::JLPT_LEVELS->label(),
            $values,
        );
    }
}
