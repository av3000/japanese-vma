<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Resources;

use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Enum-typed accessors keep `FlashcardField`, `AnswerMode` and `ScriptStrictness` as shared
 * schema components, the same way FlashcardDeckResource does.
 *
 * @property StudySession $resource
 */
class StudySessionResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(StudySession $session)
    {
        parent::__construct($session);
    }

    /**
     * @return array{
     *     uuid: string,
     *     catalogue_uuid: string|null,
     *     catalogue_type: int,
     *     config: array{prompt: FlashcardField, answer: FlashcardField, mode: AnswerMode, script: ScriptStrictness},
     *     card_count: int,
     *     correct_count: int|null,
     *     started_at: string,
     *     completed_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource;

        return [
            'uuid' => $session->getUuid()->value(),
            'catalogue_uuid' => $session->getCatalogueUuid()?->value(),
            'catalogue_type' => (int) $session->getCatalogueType()->value,
            'config' => [
                'prompt' => $this->prompt(),
                'answer' => $this->answer(),
                'mode' => $this->mode(),
                'script' => $this->script(),
            ],
            'card_count' => (int) $session->getCardCount(),
            'correct_count' => $session->getCorrectCount() === null ? null : (int) $session->getCorrectCount(),
            'started_at' => $session->getStartedAt()->format('c'),
            'completed_at' => $session->getCompletedAt()?->format('c'),
        ];
    }

    private function prompt(): FlashcardField
    {
        return $this->resource->getPrompt();
    }

    private function answer(): FlashcardField
    {
        return $this->resource->getAnswer();
    }

    private function mode(): AnswerMode
    {
        return $this->resource->getMode();
    }

    private function script(): ScriptStrictness
    {
        return $this->resource->getScript();
    }
}
