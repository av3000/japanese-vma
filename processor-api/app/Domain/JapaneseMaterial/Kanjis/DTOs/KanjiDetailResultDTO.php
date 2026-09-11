<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\DTOs;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;

final readonly class KanjiDetailResultDTO
{
    /**
     * @param array<int, ArticleListItemDTO>|null $articles
     */
    public function __construct(
        public Kanji $kanji,
        public ?WordListResultDTO $words = null,
        public ?SentenceListResultDTO $sentences = null,
        public ?array $articles = null,
        public ?ViewerCatalogueStateDTO $viewerCatalogueState = null,
    ) {
    }
}
