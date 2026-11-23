<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\DTOs;

final readonly class SentenceWriteDTO
{
    public function __construct(
        public string $content,
    ) {
    }

    /**
     * @param array{content: string} $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            content: trim($validated['content']),
        );
    }
}
