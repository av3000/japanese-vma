<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Shared\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleStatusRequest extends FormRequest
{
    /**
     * The route is already behind `auth:api` + `checkRole:admin`; mirroring
     * StoreArticleRequest here is what makes Scramble document the 403 response.
     */
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', Rule::enum(ArticleStatus::class)],
        ];
    }
}
