<?php

namespace App\Http\v1\Comments\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The reply list has no sort or nesting controls: a reply thread always reads
 * forwards, and the subtree is already flattened.
 */
class IndexCommentRepliesRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'page.min' => 'Page must be at least 1',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
        ];
    }
}
