<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use App\Domain\Study\ValueObjects\FlashcardConfig;
use Illuminate\Foundation\Http\FormRequest;

class CompleteStudySessionRequest extends FormRequest
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
            'correct_count' => ['required', 'integer', 'min:0', 'max:'.FlashcardConfig::MAX_COUNT],
        ];
    }
}
