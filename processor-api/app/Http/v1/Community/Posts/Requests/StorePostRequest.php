<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Requests;

use App\Domain\Community\Posts\Enums\PostTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Title and content bounds match the legacy PostStoreRequest so existing drafts
 * stay acceptable. `topic` replaces the legacy unvalidated `type`, which let any
 * value reach the column and produced Posts the canonical mapping cannot label.
 */
class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['title', 'content'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }

        // Accepts the legacy `type` field name so a caller mid-migration is not
        // rejected for using the only name the old endpoint knew.
        if (! $this->exists('topic') && $this->exists('type')) {
            $normalized['topic'] = $this->input('type');
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'content' => ['required', 'string', 'min:5', 'max:15000'],
            'topic' => ['required', 'integer', Rule::in(array_column(PostTopic::cases(), 'value'))],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'topic.in' => 'Topic must be one of the supported post topics.',
            'tags.max' => 'Maximum 10 hashtags allowed',
            'tags.*.distinct' => 'Duplicate hashtags are not allowed',
        ];
    }
}
