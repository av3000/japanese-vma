<?php

namespace App\Http\v1\Articles\Requests;

use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Foundation\Http\FormRequest;

class IndexPendingArticlesRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:'.Pagination::MIN_PAGE],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.Pagination::MAX_PER_PAGE],
        ];
    }
}
