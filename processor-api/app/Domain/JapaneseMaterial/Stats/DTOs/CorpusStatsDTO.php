<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Stats\DTOs;

/**
 * How much study material the platform holds, shown on the landing page.
 */
final readonly class CorpusStatsDTO
{
    public function __construct(
        public int $radicals,
        public int $kanjis,
        public int $words,
        public int $sentences,
    ) {
    }

    /**
     * @return array{radicals: int, kanjis: int, words: int, sentences: int}
     */
    public function toArray(): array
    {
        return [
            'radicals' => $this->radicals,
            'kanjis' => $this->kanjis,
            'words' => $this->words,
            'sentences' => $this->sentences,
        ];
    }

    /**
     * @param array{radicals: int, kanjis: int, words: int, sentences: int} $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            radicals: (int) $values['radicals'],
            kanjis: (int) $values['kanjis'],
            words: (int) $values['words'],
            sentences: (int) $values['sentences'],
        );
    }
}
