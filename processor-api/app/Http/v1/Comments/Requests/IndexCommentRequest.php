<?php

namespace App\Http\v1\Comments\Requests;

use App\Domain\Comments\DTOs\CommentListDTO;
use Illuminate\Foundation\Http\FormRequest;

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
            'replies_limit' => 'sometimes|integer|min:0|max:'.CommentListDTO::MAX_REPLIES_LIMIT,
            'sort_by' => 'sometimes|string|in:created_at,updated_at',
            'sort_dir' => 'sometimes|string|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'page.min' => 'Page must be at least 1',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
            'replies_limit.min' => 'Replies limit cannot be negative',
            'replies_limit.max' => 'Replies limit cannot exceed '.CommentListDTO::MAX_REPLIES_LIMIT,
            'sort_by.in' => 'Sort field must be either created_at or updated_at',
            'sort_dir.in' => 'Sort direction must be either asc or desc',
            'include_replies.boolean' => 'Include replies must be a boolean value',
        ];
    }

    public function attributes(): array
    {
        return [
            'include_replies' => 'include nested replies',
            'replies_limit' => 'replies per comment',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('include_replies')) {
            $data['include_replies'] = $this->boolean('include_replies');
        }

        $this->merge($data);
    }
}
