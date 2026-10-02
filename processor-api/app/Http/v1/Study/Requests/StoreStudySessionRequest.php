<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudySessionRequest extends FormRequest
{
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
            'answer' => ['required', Rule::enum(FlashcardField::class), 'different:prompt'],
            'mode' => ['required', Rule::enum(AnswerMode::class)],
            'script' => ['sometimes', Rule::enum(ScriptStrictness::class)],
            'card_count' => ['required', 'integer', 'min:'.FlashcardConfig::MIN_COUNT, 'max:'.FlashcardConfig::MAX_COUNT],
        ];
    }
}
