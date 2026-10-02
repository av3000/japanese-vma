<?php

declare(strict_types=1);

namespace App\Domain\Study\DTOs;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;

final readonly class StudySessionCreateDTO
{
    public function __construct(
        public EntityId $catalogueUuid,
        public FlashcardField $prompt,
        public FlashcardField $answer,
        public AnswerMode $mode,
        public ScriptStrictness $script,
        public int $cardCount,
    ) {
    }

    /**
     * @param array{catalogue_uuid: string, prompt: string, answer: string, mode: string, script?: string|null, card_count: int|string} $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            catalogueUuid: EntityId::from($validated['catalogue_uuid']),
            prompt: FlashcardField::from($validated['prompt']),
            answer: FlashcardField::from($validated['answer']),
            mode: AnswerMode::from($validated['mode']),
            script: ScriptStrictness::from($validated['script'] ?? ScriptStrictness::STRICT->value),
            cardCount: (int) $validated['card_count'],
        );
    }
}
