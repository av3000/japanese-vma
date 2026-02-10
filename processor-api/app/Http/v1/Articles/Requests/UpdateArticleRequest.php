<?php

namespace App\Http\v1\Articles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
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
        if (!$this->has('hashtags') && $this->has('tags')) {
            $this->merge(['hashtags' => $this->input('tags')]);
        }
    }

    /**
     * Get the list of updateable fields.
     */
    private function getUpdateableFields(): array
    {
        return [
            'title_jp',
            'title_en',
            'content_jp',
            'content_en',
            'source_link',
            'publicity',
            'hashtags',
            'tags'
        ];
    }

    /**
     * Check if request contains at least one updateable field.
     */
    public function hasAnyUpdateableFields(): bool
    {
        return collect($this->getUpdateableFields())
            ->some(fn($field) => $this->has($field));
    }
}
