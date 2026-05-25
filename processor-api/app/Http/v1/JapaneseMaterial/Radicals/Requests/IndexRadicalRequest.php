<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexRadicalRequest extends FormRequest
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
            'strokes',
        ]));
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'min:1', 'max:100'],
            'radical' => ['nullable', 'string', 'min:1', 'max:30'],
            'meaning' => ['nullable', 'string', 'min:1', 'max:100'],
            'hiragana' => ['nullable', 'string', 'min:1', 'max:100'],
            'strokes' => ['nullable', 'integer', 'min:1', 'max:30'],
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
