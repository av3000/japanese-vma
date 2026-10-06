<?php

declare(strict_types=1);

namespace App\Http\v1\Study\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudyAttemptRequest extends FormRequest
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
            'item_id' => ['required', 'integer', 'min:1'],
            // 1 for the first pass, 2 for "retry missed" within the same session, and so on.
            'attempt_no' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'given_answer' => ['nullable', 'string', 'max:500'],
            'expected_answers' => ['required', 'array', 'min:1', 'max:50'],
            'expected_answers.*' => ['string', 'max:500'],
            'is_correct' => ['required', 'boolean'],
            'response_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ];
    }
}
