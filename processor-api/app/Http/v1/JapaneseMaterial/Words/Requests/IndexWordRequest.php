<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexWordRequest extends FormRequest
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
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'min:1', 'max:255'],
            'word' => ['nullable', 'string', 'min:1', 'max:255'],
            'furigana' => ['nullable', 'string', 'min:1', 'max:255'],
            'jlpt' => ['nullable', 'string', 'min:1', 'max:20'],
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
