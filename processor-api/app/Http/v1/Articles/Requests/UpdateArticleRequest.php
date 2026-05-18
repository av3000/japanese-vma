<?php

namespace App\Http\v1\Articles\Requests;

use App\Shared\Http\TypedResults;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateArticleRequest extends FormRequest
{
    private const UPDATEABLE_FIELDS = [
        'title_jp',
        'title_en',
        'content_jp',
        'content_en',
        'source_link',
        'publicity',
        'hashtags',
        'tags',
    ];

    private const EMPTY_UPDATE_MESSAGE = 'At least one field must be provided for update operation';

    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title_jp' => 'sometimes|string|min:2|max:255',
            'title_en' => 'sometimes|nullable|string|min:2|max:255',
            'content_jp' => 'sometimes|string|min:10|max:2000',
            'content_en' => 'sometimes|nullable|string|min:10|max:2000',
            'source_link' => 'sometimes|url|max:500',
            'publicity' => 'sometimes|boolean',
            'hashtags' => 'sometimes|array|max:10',
            'hashtags.*' => 'string|max:50|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'title_jp.min' => 'Japanese title must be at least 2 characters',
            'content_jp.min' => 'Japanese content must be at least 10 characters',
            'source_link.url' => 'Source link must be a valid URL',
            'hashtags.max' => 'Maximum 10 hashtags allowed',
            'hashtags.*.max' => 'Each hashtag must not exceed 50 characters',
            'hashtags.*.distinct' => 'Duplicate hashtags are not allowed',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('hashtags') && $this->exists('tags')) {
            $this->merge(['hashtags' => $this->input('tags')]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            if ($validator->errors()->isNotEmpty() || $this->hasAnyUpdateableFields()) {
                return;
            }

            throw new HttpResponseException(
                TypedResults::validationProblem(
                    ['fields' => [self::EMPTY_UPDATE_MESSAGE]],
                    'No fields to update',
                )
            );
        });
    }

    private function hasAnyUpdateableFields(): bool
    {
        return collect(self::UPDATEABLE_FIELDS)
            ->some(fn (string $field): bool => $this->exists($field));
    }
}
