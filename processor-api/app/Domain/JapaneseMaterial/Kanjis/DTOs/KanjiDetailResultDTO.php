<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\DTOs;

use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;

final readonly class KanjiDetailResultDTO
{
    public function __construct(
        public Kanji $kanji,
        public ?WordListResultDTO $words = null,
        public ?SentenceListResultDTO $sentences = null,
        public ?ArticleListResultDTO $articles = null,
        public ?ViewerCatalogueStateDTO $viewerCatalogueState = null,
    ) {
    }
}
