<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSentenceRequest extends FormRequest
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
            'user_id',
        ]));
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'min:1', 'max:100'],
            'content' => ['nullable', 'string', 'min:1', 'max:300'],
            'tatoeba_entry' => ['nullable', 'string', 'min:1', 'max:255'],
            'user_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @param array<int, string> $fields
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
