<?php

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;
use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;

readonly class ArticlePdfExportData
{
    /**
     * @param array<int, array<string, mixed>> $kanjis
     * @param array<int, DomainWord> $words
     */
    public function __construct(
        public Article $article,
        public array $kanjis = [],
        public array $words = [],
    ) {
    }
}
