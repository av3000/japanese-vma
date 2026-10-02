<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Resources;

use App\Domain\Study\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The two list fields are read through Flashcard getters, not its public properties:
 * Scramble types an array's items from the getter's `@return`, the same way KanjiResource
 * gets `onyomi: string[]`. Reading the property leaves the generated client with `unknown[]`.
 *
 * @property Flashcard $resource
 */
class FlashcardResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(Flashcard $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     item_id: int,
     *     item_uuid: string,
     *     prompt: array{text: string, hint: string|null},
     *     accepted_answers: array<int, string>,
     *     display_answer: string,
     *     options: array<int, string>|null,
     *     meta: array{jlpt: string|null, grade: string|null, strokes: int|null}
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Flashcard $card */
        $card = $this->resource;

        return [
            'item_id' => (int) $card->itemId,
            'item_uuid' => $card->itemUuid->value(),
            'prompt' => [
                'text' => (string) $card->promptText,
                'hint' => $card->promptHint === null ? null : (string) $card->promptHint,
            ],
            'accepted_answers' => $card->getAcceptedAnswers(),
            'display_answer' => (string) $card->displayAnswer,
            'options' => $card->getOptions(),
            'meta' => [
                'jlpt' => $card->jlpt === null ? null : (string) $card->jlpt,
                'grade' => $card->grade === null ? null : (string) $card->grade,
                'strokes' => $card->strokes === null ? null : (int) $card->strokes,
            ],
        ];
    }
}
