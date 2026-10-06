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

class StoreStudySessionRequest extends FormRequest
{
    use ValidatesFlashcardQuestion;

    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catalogue_uuid' => ['required', 'string', 'uuid'],
            'prompt' => ['required', Rule::enum(FlashcardField::class)],
            'answer' => ['required', Rule::enum(FlashcardField::class)],
            'mode' => ['required', Rule::enum(AnswerMode::class)],
            'script' => ['sometimes', Rule::enum(ScriptStrictness::class)],
            'card_count' => ['required', 'integer', 'min:'.FlashcardConfig::MIN_COUNT, 'max:'.FlashcardConfig::MAX_COUNT],
        ];
    }

    /** Prompt and answer must differ and a character answer cannot be typed; the type-specific rules are the service's. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->addQuestionRuleErrors($validator));
    }
}
