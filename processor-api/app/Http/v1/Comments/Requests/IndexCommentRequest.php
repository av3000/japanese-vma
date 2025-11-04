<?php
namespace App\Http\v1\Comments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\Shared\Enums\ObjectTemplateType;

class IndexCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'include_replies' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:created_at,updated_at',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'include_likes' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'page.min' => 'Page must be at least 1',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
            'sort_by.in' => 'Sort field must be either created_at or updated_at',
            'sort_dir.in' => 'Sort direction must be either asc or desc',
            'include_replies.boolean' => 'Include replies must be a boolean value',
            'include_likes.boolean' => 'Include likes must be a boolean value',
        ];
    }

    public function attributes(): array
    {
        return [
            'include_replies' => 'include nested replies',
            'include_likes' => 'include likes count',
        ];
    }
}
