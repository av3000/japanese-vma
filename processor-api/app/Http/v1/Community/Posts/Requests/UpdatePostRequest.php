<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Requests;

use App\Domain\Community\Posts\Enums\PostTopic;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Partial update, matching the legacy `isset()` behaviour: any subset of the
 * fields may be sent and the rest are left alone. An empty body is rejected
 * rather than silently touching nothing.
 */
class UpdatePostRequest extends FormRequest
{
    private const UPDATABLE_FIELDS = ['title', 'content', 'topic', 'tags'];

    private const EMPTY_UPDATE_MESSAGE = 'At least one field must be provided for update operation';

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
            'title' => ['sometimes', 'string', 'min:2', 'max:255'],
            'content' => ['sometimes', 'string', 'min:5', 'max:15000'],
            'topic' => ['sometimes', 'integer', Rule::in(array_column(PostTopic::cases(), 'value'))],
            // `sometimes` rather than `nullable`: sending `tags: []` clears every
            // tag, so the empty array has to survive validation as a real value.
            'tags' => ['sometimes', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (self::UPDATABLE_FIELDS as $field) {
                if ($this->exists($field)) {
                    return;
                }
            }

            $validator->errors()->add('post', self::EMPTY_UPDATE_MESSAGE);
        });
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
