<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Mcp\Support\ArticleMainLevel;

/**
 * Shapes an article of a search page for an assistant: enough to pick one to read and to judge how
 * hard it is, and none of the integer ids, author details, engagement counts or processing state
 * the website's own resources carry.
 */
final class ArticleSummaryPresenter
{
    /**
     * @return array{
     *     uuid: string,
     *     title_jp: string,
     *     title_en: ?string,
     *     preview_jp: string,
     *     main_level: ?string,
     *     level_mix: array{N5: int, N4: int, N3: int, N2: int, N1: int, uncommon: int},
     *     tags: array<int, string>,
     *     created_at: string
     * }
     */
    public function present(ArticleListItemDTO $item): array
    {
        $article = $item->article;
        $counts = $article->getJlptLevels();

        return [
            'uuid' => $article->getUid()->value(),
            'title_jp' => (string) $article->getTitleJp(),
            'title_en' => $article->getTitleEn()?->value,
            'preview_jp' => $article->getContentJp()->excerpt(),
            'main_level' => ArticleMainLevel::fromCounts($counts)?->value,
            'level_mix' => [
                'N5' => $counts->n5,
                'N4' => $counts->n4,
                'N3' => $counts->n3,
                'N2' => $counts->n2,
                'N1' => $counts->n1,
                'uncommon' => $counts->uncommon,
            ],
            'tags' => array_values(array_map(
                static fn (array|object $hashtag): string => (string) data_get($hashtag, 'content'),
                $item->hashtags,
            )),
            'created_at' => $article->getCreatedAt()->format('c'),
        ];
    }
}
