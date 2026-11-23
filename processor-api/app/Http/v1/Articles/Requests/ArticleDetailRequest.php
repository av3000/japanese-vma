<?php

namespace App\Http\v1\Articles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint, authorization handled in service
    }

    public function rules(): array
    {
        return [
            'include_kanjis' => 'sometimes|boolean',
            'include_words' => 'sometimes|boolean',
            'include_comments' => 'sometimes|boolean',
            'include_views' => 'sometimes|boolean',
            'include_downloads' => 'sometimes|boolean',
            'include_likes' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'include_kanjis.boolean' => 'Include kanjis must be a boolean value',
            'include_words.boolean' => 'Include words must be a boolean value',
            'include_comments.boolean' => 'Include comments must be a boolean value',
            'include_views.boolean' => 'Include views must be a boolean value',
            'include_downloads.boolean' => 'Include downloads must be a boolean value',
            'include_likes.boolean' => 'Include likes must be a boolean value',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'include_kanjis',
            'include_words',
            'include_comments',
            'include_views',
            'include_downloads',
            'include_likes',
        ];

        $normalized = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $normalized[$field] = filter_var(
                    $this->input($field),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
            }
        }

        $this->merge($normalized);
    }
}
