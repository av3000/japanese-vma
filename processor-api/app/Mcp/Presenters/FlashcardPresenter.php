<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use App\Mcp\Support\DeckKind;
use App\Mcp\Support\StudyLevel;
use LogicException;

/**
 * Shapes a flashcard deck for an assistant that quizzes a learner: the question each card asks and
 * the answers it accepts, and none of the integer ids, item uuids, grades, stroke counts, script
 * strictness or exclusion counts the website's own resource carries.
 */
final class FlashcardPresenter
{
    /**
     * @return array{deck: array{uuid: string, title: string, kind: string}, config: array{prompt: string, answer: string, mode: string, count: int, seed: int}, cards: array<int, array<string, mixed>>, total_items: int, eligible_items: int}
     */
    public function presentDeck(FlashcardDeckDTO $deck): array
    {
        $catalogue = $deck->catalogue;
        $question = $deck->config->question;

        return [
            'deck' => [
                'uuid' => $catalogue->getUid()->value(),
                'title' => (string) $catalogue->getTitle(),
                'kind' => $this->kindOf($deck)->value,
            ],
            'config' => [
                'prompt' => $question->prompt->value,
                'answer' => $question->answer->value,
                'mode' => $question->mode->value,
                'count' => $deck->config->count,
                'seed' => $deck->config->seed,
            ],
            'cards' => array_map($this->presentCard(...), $deck->cards),
            'total_items' => $deck->totalItems,
            'eligible_items' => $deck->eligibleItems,
        ];
    }

    /**
     * @return array{prompt: string, hint: ?string, answer: string, accepted_answers: array<int, string>, options: ?array<int, string>, level: ?string}
     */
    public function presentCard(Flashcard $card): array
    {
        return [
            'prompt' => $card->promptText,
            'hint' => $card->promptHint,
            'answer' => $card->displayAnswer,
            'accepted_answers' => $card->getAcceptedAnswers(),
            'options' => $card->getOptions(),
            'level' => StudyLevel::fromDictionary($card->jlpt)?->value,
        ];
    }

    /**
     * A "known" list (known kanji, known words) holds the same things as its custom twin, so it reports the twin's kind.
     * The deck service only builds decks from those six list types, so no other type reaches here.
     */
    private function kindOf(FlashcardDeckDTO $deck): DeckKind
    {
        $type = $deck->catalogue->getType();
        $family = FlashcardQuestion::baseType($type) ?? $type;

        return DeckKind::fromListType($family) ?? throw new LogicException("A {$type->label()} list cannot be a flashcard deck.");
    }
}
