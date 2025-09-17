<?php

namespace App\Http\v1\Article\Requests;
namespace App\Http\v1\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title_jp' => 'required|string|min:2|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_jp' => 'required|string|min:10|max:2000',
            'content_en' => 'nullable|string|max:2000',
            'source_link' => 'required|url|max:500',
            'publicity' => 'nullable|boolean',
            'tags' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title_jp.required' => 'Japanese title is required',
            'title_jp.min' => 'Japanese title must be at least 2 characters',
            'content_jp.required' => 'Japanese content is required',
            'content_jp.min' => 'Japanese content must be at least 10 characters',
            'source_link.required' => 'Source link is required',
            'source_link.url' => 'Source link must be a valid URL',
        ];
    }

    protected function prepareForValidation(): void
    {

    }
}
