<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleKanjisIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All users can see kanjis for an article
    }

    public function rules(): array
    {
        return [
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
