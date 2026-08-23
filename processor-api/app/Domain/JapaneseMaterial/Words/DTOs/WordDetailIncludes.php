<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\DTOs;

final readonly class WordDetailIncludes
{
    public const KANJIS = 'kanjis';

    public const ARTICLES = 'articles';

    public const ALLOWED = [self::KANJIS, self::ARTICLES];

    public function __construct(
        public bool $kanjis = false,
        public bool $articles = false,
    ) {
    }

    public static function fromCsv(?string $include): self
    {
        $values = $include === null
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $include))));

        return new self(
            kanjis: in_array(self::KANJIS, $values, true),
            articles: in_array(self::ARTICLES, $values, true),
        );
    }
}
