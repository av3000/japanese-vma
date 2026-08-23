<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\DTOs;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Words\Models\Word;

final readonly class WordDetailResultDTO
{
    /**
     * @param array<int, Kanji>|null $kanjis
     * @param array<int, ArticleListItemDTO>|null $articles
     */
    public function __construct(
        public Word $word,
        public ?array $kanjis = null,
        public ?array $articles = null,
    ) {
    }
}
