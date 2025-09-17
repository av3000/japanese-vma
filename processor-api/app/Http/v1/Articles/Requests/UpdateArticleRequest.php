<?php

namespace App\Http\v1\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title_jp' => 'sometimes|string|min:2|max:255',
            'title_en' => 'sometimes|nullable|string|max:255',
            'content_jp' => 'sometimes|string|min:10|max:10000',
            'content_en' => 'sometimes|nullable|string|max:10000',
            'source_link' => 'sometimes|url|max:500',
            'publicity' => 'sometimes|boolean',
            'status' => 'sometimes|integer|between:0,3',
            'tags' => 'sometimes|nullable|string|max:255',
            'reattach' => 'sometimes|boolean',
        ];
    }
}
