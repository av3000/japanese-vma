<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Requests;

use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->castIntegerFields([
            'page',
            'per_page',
            'topic',
        ]));
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.PostQueryCriteria::MAX_PER_PAGE],
            'keyword' => ['nullable', 'string', 'min:1', 'max:255'],
            'hashtag' => ['nullable', 'string', 'min:1', 'max:100'],
            'topic' => ['nullable', 'integer', Rule::enum(PostTopic::class)],
            'sort' => ['nullable', Rule::enum(PostSort::class)],
        ];
    }

    /**
     * @param array<int, string> $fields
     *
     * @return array<string, int>
     */
    private function castIntegerFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $normalized[$field] = (int) $value;
            }
        }

        return $normalized;
    }
}
