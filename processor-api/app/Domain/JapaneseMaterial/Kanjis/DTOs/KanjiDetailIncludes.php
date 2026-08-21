<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\DTOs;

final readonly class KanjiDetailIncludes
{
    public const WORDS = 'words';

    public const SENTENCES = 'sentences';

    public const ARTICLES = 'articles';

    public const VIEWER_CATALOGUE_STATE = 'viewer_catalogue_state';

    public const ALLOWED = [
        self::WORDS,
        self::SENTENCES,
        self::ARTICLES,
        self::VIEWER_CATALOGUE_STATE,
    ];

    public function __construct(
        public bool $words = false,
        public bool $sentences = false,
        public bool $articles = false,
        public bool $viewerCatalogueState = false,
    ) {
    }

    public static function fromCsv(?string $include): self
    {
        $values = $include === null
            ? []
            : array_map('trim', explode(',', $include));

        return new self(
            words: in_array(self::WORDS, $values, true),
            sentences: in_array(self::SENTENCES, $values, true),
            articles: in_array(self::ARTICLES, $values, true),
            viewerCatalogueState: in_array(self::VIEWER_CATALOGUE_STATE, $values, true),
        );
    }
}
