<?php

declare(strict_types=1);

namespace App\Domain\Study\DTOs;

/**
 * One answer to one card. The verdict is the client's (epic #413); `expectedAnswers` is
 * kept beside it so a later server-side re-grade stays possible.
 */
final readonly class StudyAttemptDTO
{
    /**
     * @param list<string> $expectedAnswers
     */
    public function __construct(
        public int $itemId,
        public int $attemptNo,
        public ?string $givenAnswer,
        public array $expectedAnswers,
        public bool $isCorrect,
        public ?int $responseMs,
    ) {
    }

    /**
     * @param array{item_id: int|string, attempt_no?: int|string|null, given_answer?: string|null, expected_answers: array<int, string>, is_correct: bool, response_ms?: int|string|null} $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            itemId: (int) $validated['item_id'],
            attemptNo: (int) ($validated['attempt_no'] ?? 1),
            givenAnswer: isset($validated['given_answer']) ? (string) $validated['given_answer'] : null,
            expectedAnswers: array_values(array_map('strval', $validated['expected_answers'])),
            isCorrect: (bool) $validated['is_correct'],
            responseMs: isset($validated['response_ms']) ? (int) $validated['response_ms'] : null,
        );
    }
}
