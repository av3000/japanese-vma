<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\ValueObjects\FlashcardQuestion;
use Illuminate\Validation\Validator;

/**
 * Reports FlashcardQuestion's type-independent rules as field errors, so a request is
 * rejected with a 422 that names the field instead of reaching the value object and
 * throwing. The rules themselves live on FlashcardQuestion; the type-specific combination
 * rules need the catalogue and are answered by the service.
 */
trait ValidatesFlashcardQuestion
{
    /**
     * @param array{prompt?: string, answer?: string, mode?: string} $defaults Values used when a field is omitted.
     */
    protected function addQuestionRuleErrors(Validator $validator, array $defaults = []): void
    {
        $prompt = FlashcardField::tryFrom((string) $this->input('prompt', $defaults['prompt'] ?? ''));
        $answer = FlashcardField::tryFrom((string) $this->input('answer', $defaults['answer'] ?? ''));
        $mode = AnswerMode::tryFrom((string) $this->input('mode', $defaults['mode'] ?? ''));

        // An unknown or missing value is already an error from the enum and required rules.
        if ($prompt === null || $answer === null || $mode === null) {
            return;
        }

        if (! FlashcardQuestion::fieldsDiffer($prompt, $answer)) {
            $validator->errors()->add('answer', 'The answer field must differ from the prompt field.');
        }

        if (! FlashcardQuestion::modeFitsAnswer($mode, $answer)) {
            $validator->errors()->add('mode', 'A character answer cannot be typed; use options.');
        }
    }
}
