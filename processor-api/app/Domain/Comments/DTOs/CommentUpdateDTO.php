<?php

namespace App\Domain\Comments\DTOs;

readonly class CommentUpdateDTO
{
    public function __construct(
        public string $content,
    ) {
    }

    /**
     * @param array{content: string} $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            content: $validated['content'],
        );
    }
}
