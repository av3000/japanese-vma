<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Resources;

use App\Domain\Study\DTOs\FlashcardDeckDTO;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The class-level `@property` lets Scramble resolve `$this->resource`; the enum-typed
 * accessors below are what make `FlashcardField`, `AnswerMode` and `ScriptStrictness`
 * reusable components in the OpenAPI document instead of inline string unions, the same
 * mechanism ProcessingStatusResource uses for `status` (issue #259).
 *
 * @property FlashcardDeckDTO $resource
 */
class FlashcardDeckResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(FlashcardDeckDTO $deck)
    {
        parent::__construct($deck);
    }

    /**
     * @return array{
     *     catalogue: array{uuid: string, title: string, type: int, type_label: string},
     *     config: array{prompt: FlashcardField, answer: FlashcardField, mode: AnswerMode, script: ScriptStrictness, count: int, seed: int},
     *     cards: array<int, FlashcardResource>,
     *     total_items: int,
     *     eligible_items: int,
     *     excluded: array{empty_answer_field: int}
     * }
     */
    public function toArray(Request $request): array
    {
        $deck = $this->resource;
        $catalogue = $deck->catalogue;

        return [
            'catalogue' => [
                'uuid' => (string) $catalogue->getUid(),
                'title' => (string) $catalogue->getTitle(),
                'type' => (int) $catalogue->getType()->value,
                'type_label' => (string) $catalogue->getTypeLabel(),
            ],
            'config' => [
                'prompt' => $this->prompt(),
                'answer' => $this->answer(),
                'mode' => $this->mode(),
                'script' => $this->script(),
                'count' => (int) $deck->config->count,
                'seed' => (int) $deck->config->seed,
            ],
            'cards' => FlashcardResource::collection($deck->cards),
            'total_items' => (int) $deck->totalItems,
            'eligible_items' => (int) $deck->eligibleItems,
            'excluded' => [
                'empty_answer_field' => (int) $deck->excludedEmptyAnswerField,
            ],
        ];
    }

    private function prompt(): FlashcardField
    {
        return $this->resource->config->prompt;
    }

    private function answer(): FlashcardField
    {
        return $this->resource->config->answer;
    }

    private function mode(): AnswerMode
    {
        return $this->resource->config->mode;
    }

    private function script(): ScriptStrictness
    {
        return $this->resource->config->script;
    }
}
