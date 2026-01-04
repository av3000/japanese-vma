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
            'title_en' => 'sometimes|nullable|string|max:255',
            'content_jp' => 'sometimes|string|min:10|max:2000',
            'content_en' => 'sometimes|nullable|string|max:2000',
            'source_link' => 'sometimes|url|max:500',
            'publicity' => 'sometimes|boolean',
            'status' => 'sometimes|integer|between:0,3',
            'tags' => 'sometimes|nullable|array|max:10',
            'tags.*' => 'string|max:50|distinct',
            'reattach' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title_jp.min' => 'Japanese title must be at least 2 characters',
            'content_jp.min' => 'Japanese content must be at least 10 characters',
            'source_link.url' => 'Source link must be a valid URL',
            'tags.max' => 'Maximum 10 tags allowed',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
            'tags.*.distinct' => 'Duplicate tags are not allowed',
        ];
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
            'status',
            'tags',
            'reattach'
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
