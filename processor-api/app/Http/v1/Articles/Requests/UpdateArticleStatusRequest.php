<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Shared\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', Rule::enum(ArticleStatus::class)],
        ];
    }
}
