<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Requests;

use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Paging for the article attachment lists, `articles/{uuid}/kanjis` and `articles/{uuid}/words`.
 *
 * Both are public routes; who may read them is the article's own visibility, decided in the
 * service, not here.
 */
class ArticleAttachmentListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:'.Pagination::MIN_PAGE,
            'per_page' => 'sometimes|integer|min:1|max:'.Pagination::MAX_PER_PAGE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.min' => 'Page must be at least '.Pagination::MIN_PAGE,
            'per_page.max' => 'Per page may not be greater than '.Pagination::MAX_PER_PAGE,
        ];
    }

    public function pagination(): Pagination
    {
        /** @var array{page?: int|string, per_page?: int|string} $validated */
        $validated = $this->validated();

        // Query parameters arrive as strings even after integer validation.
        return Pagination::fromInputOrDefault(
            isset($validated['page']) ? (int) $validated['page'] : null,
            isset($validated['per_page']) ? (int) $validated['per_page'] : null,
        );
    }
}
