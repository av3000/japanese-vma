<?php

namespace App\Http\v1\Articles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title_jp' => 'required|string|min:2|max:255',
            'title_en' => 'required|string|max:255',
            'content_jp' => 'required|string|min:10|max:2000',
            'content_en' => 'nullable|string|min:10|max:2000',
            'source_link' => 'required|url|max:500',
            'publicity' => 'required|boolean',
            'tags' => 'nullable|array|max:10', // TODO: Also handle tags logic in business layer
            'tags.*' => 'string|max:50|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'title_jp.required' => 'Japanese title is required',
            'title_en.required' => 'English title is required',
            'title_jp.min' => 'Japanese title must be at least 2 characters',
            'content_jp.required' => 'Japanese content is required',
            'content_jp.min' => 'Japanese content must be at least 10 characters',
            'content_en.min' => 'English content must be at least 10 characters',
            'source_link.required' => 'Source link is required',
            'source_link.url' => 'Source link must be a valid URL',
        ];
    }

    protected function prepareForValidation(): void {}
}
