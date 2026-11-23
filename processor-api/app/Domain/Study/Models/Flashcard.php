<?php

declare(strict_types=1);

namespace App\Domain\Study\Models;

use App\Domain\Shared\ValueObjects\EntityId;

/**
 * One catalogue item seen through a FlashcardConfig: a prompt side and an answer side.
 *
 * `acceptedAnswers` are the raw stored values (kun'yomi keeps its okurigana dot, glosses
 * keep their case); the client normalizes both sides when grading. `displayAnswer` is the
 * one value shown as an option or in feedback.
 */
final readonly class Flashcard
{
    /**
     * @param array<int, string> $acceptedAnswers
     * @param array<int, string>|null $options Four shuffled choices in options mode, null otherwise.
     */
    public function __construct(
        public int $itemId,
        public EntityId $itemUuid,
        public string $promptText,
        public ?string $promptHint,
        public array $acceptedAnswers,
        public string $displayAnswer,
        public ?array $options,
        public ?string $jlpt,
        public ?string $grade,
        public ?int $strokes,
    ) {
    }

    /**
     * Getter twins of the public list properties. KanjiResource reads lists through getters
     * like these because Scramble resolves an array's element type from the getter's
     * `@return`; reading the property directly leaves the generated client with `unknown[]`.
     *
     * @return array<int, string>
     */
    public function getAcceptedAnswers(): array
    {
        return $this->acceptedAnswers;
    }

    /**
     * @return array<int, string>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param array<int, string> $options
     */
    public function withOptions(array $options): self
    {
        return new self(
            $this->itemId,
            $this->itemUuid,
            $this->promptText,
            $this->promptHint,
            $this->acceptedAnswers,
            $this->displayAnswer,
            $options,
            $this->jlpt,
            $this->grade,
            $this->strokes,
        );
    }
}
