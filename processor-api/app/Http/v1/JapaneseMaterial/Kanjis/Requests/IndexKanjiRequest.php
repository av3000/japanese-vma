<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiGrade;
// use App\Domain\Shared\Enums\{SortDirection, KanjiSortField};
use Illuminate\Validation\Rule;

class IndexKanjiRequest extends FormRequest
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
            'limit',
            'offset',
            'min_stroke_count',
            'max_stroke_count',
        ]));
    }

    public function rules(): array
    {
        return [
            'article_uuid' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'character' => ['nullable', 'string', 'regex:/^\p{Han}$/u', 'max:1'],
            'grade' => ['nullable', 'string', Rule::in(KanjiGrade::VALID_GRADES)],
            'jlpt' => ['nullable', 'string', Rule::in(JlptLevel::VALID_LEVELS)],
            'min_stroke_count' => ['nullable', 'integer', 'min:1'],
            'max_stroke_count' => ['nullable', 'integer', 'min:1', 'gte:min_stroke_count'],
            'meanings' => ['nullable', 'array'],
            'meanings.*' => ['string', 'min:1'],
            'onyomi' => ['nullable', 'array'],
            'onyomi.*' => ['string', 'min:1'],
            'kunyomi' => ['nullable', 'array'],
            'kunyomi.*' => ['string', 'min:1'],
            'radical' => ['nullable', 'string', 'min:1', 'max:1'],
            // 'sort_by' => ['nullable', 'string', Rule::in(array_column(KanjiSortField::cases(), 'value'))],
            // 'sort_direction' => ['nullable', 'string', Rule::in([SortDirection::ASC->value, SortDirection::DESC->value])],
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
