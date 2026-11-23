<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Kanjis\Requests;

use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ShowKanjiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $values = array_filter(array_map('trim', explode(',', (string) $value)));
                    $invalid = array_diff($values, KanjiDetailIncludes::ALLOWED);

                    if ($invalid !== []) {
                        $fail('The include field contains an unsupported Kanji detail relation.');
                    }
                },
            ],
        ];
    }

    public function includes(): KanjiDetailIncludes
    {
        return KanjiDetailIncludes::fromCsv($this->validated('include'));
    }
}
