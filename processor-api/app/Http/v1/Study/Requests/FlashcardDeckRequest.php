<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FlashcardDeckRequest extends FormRequest
{
    use ValidatesFlashcardQuestion;

    /** The question asked when the query string leaves a field out. */
    private const DEFAULTS = [
        'prompt' => FlashcardField::CHARACTER->value,
        'answer' => FlashcardField::MEANING->value,
        'mode' => AnswerMode::OPTIONS->value,
        'script' => ScriptStrictness::STRICT->value,
    ];

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->addQuestionRuleErrors($validator, self::DEFAULTS));
    }

    public function toConfig(): FlashcardConfig
    {
        $validated = [...self::DEFAULTS, ...$this->validated()];

        return new FlashcardConfig(
            question: new FlashcardQuestion(
                prompt: FlashcardField::from($validated['prompt']),
                answer: FlashcardField::from($validated['answer']),
                mode: AnswerMode::from($validated['mode']),
                script: ScriptStrictness::from($validated['script']),
            ),
            count: (int) ($validated['count'] ?? FlashcardConfig::DEFAULT_COUNT),
            seed: (int) ($validated['seed'] ?? random_int(0, FlashcardConfig::MAX_SEED)),
        );
    }
}
