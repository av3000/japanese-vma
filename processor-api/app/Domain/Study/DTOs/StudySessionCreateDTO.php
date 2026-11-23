<?php

declare(strict_types=1);

namespace App\Domain\Study\DTOs;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardQuestion;

final readonly class StudySessionCreateDTO
{
    public function __construct(
        public EntityId $catalogueUuid,
        public FlashcardQuestion $question,
        public int $cardCount,
    ) {
    }

    /**
     * The request has already rejected a prompt equal to the answer and a typed character
     * answer, so the question's own invariants hold here.
     *
     * @param array{catalogue_uuid: string, prompt: string, answer: string, mode: string, script?: string|null, card_count: int|string} $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            catalogueUuid: EntityId::from($validated['catalogue_uuid']),
            question: new FlashcardQuestion(
                prompt: FlashcardField::from($validated['prompt']),
                answer: FlashcardField::from($validated['answer']),
                mode: AnswerMode::from($validated['mode']),
                script: ScriptStrictness::from($validated['script'] ?? ScriptStrictness::STRICT->value),
            ),
            cardCount: (int) $validated['card_count'],
        );
    }
}
