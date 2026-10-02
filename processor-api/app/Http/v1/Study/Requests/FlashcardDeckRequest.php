<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FlashcardDeckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['sometimes', Rule::enum(FlashcardField::class)],
            'answer' => ['sometimes', Rule::enum(FlashcardField::class)],
            'mode' => ['sometimes', Rule::enum(AnswerMode::class)],
            'script' => ['sometimes', Rule::enum(ScriptStrictness::class)],
            'count' => ['sometimes', 'integer', 'min:'.FlashcardConfig::MIN_COUNT, 'max:'.FlashcardConfig::MAX_COUNT],
            'seed' => ['sometimes', 'integer', 'min:0', 'max:'.FlashcardConfig::MAX_SEED],
        ];
    }

    /**
     * Prompt and answer must differ, and a character answer cannot be typed. The type-specific
     * combination rules need the catalogue and are answered by the service with a 422 of their own.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $prompt = $this->input('prompt', FlashcardField::CHARACTER->value);
            $answer = $this->input('answer', FlashcardField::MEANING->value);
            $mode = $this->input('mode', AnswerMode::OPTIONS->value);

            if ($prompt === $answer) {
                $validator->errors()->add('answer', 'The answer field must differ from the prompt field.');
            }

            if ($mode === AnswerMode::TYPED->value && $answer === FlashcardField::CHARACTER->value) {
                $validator->errors()->add('mode', 'A character answer cannot be typed; use options.');
            }
        });
    }

    public function toConfig(): FlashcardConfig
    {
        $validated = $this->validated();

        return new FlashcardConfig(
            prompt: FlashcardField::from($validated['prompt'] ?? FlashcardField::CHARACTER->value),
            answer: FlashcardField::from($validated['answer'] ?? FlashcardField::MEANING->value),
            mode: AnswerMode::from($validated['mode'] ?? AnswerMode::OPTIONS->value),
            script: ScriptStrictness::from($validated['script'] ?? ScriptStrictness::STRICT->value),
            count: (int) ($validated['count'] ?? FlashcardConfig::DEFAULT_COUNT),
            seed: (int) ($validated['seed'] ?? random_int(0, FlashcardConfig::MAX_SEED)),
        );
    }
}
