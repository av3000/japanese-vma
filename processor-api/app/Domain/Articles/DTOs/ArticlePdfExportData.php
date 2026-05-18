<?php

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;

readonly class ArticlePdfExportData
{
    /**
     * @param array<int, array<string, mixed>> $kanjis
     * @param array<int, array<string, mixed>> $words
     */
    public function __construct(
        public Article $article,
        public array $kanjis = [],
        public array $words = [],
    ) {
    }
}
