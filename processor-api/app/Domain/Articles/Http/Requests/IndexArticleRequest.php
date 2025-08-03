<?php

namespace App\Domain\Articles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'category' => 'sometimes|integer',
            'search' => 'sometimes|string|max:255',
            'sort_by' => 'sometimes|string|in:created_at,updated_at,title_jp,title_en',
            'sort_dir' => 'sometimes|string|in:asc,desc,ASC,DESC',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'include_stats' => 'sometimes|in:true,false,1,0',
        ];
    }

    /**
     * Get custom attributes for validator errors (helpful for API documentation)
     */
    public function attributes(): array
    {
        return [
            'include_stats' => 'include statistics',
        ];
    }
}
