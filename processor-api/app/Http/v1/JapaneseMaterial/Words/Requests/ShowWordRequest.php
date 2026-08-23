<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Requests;

use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailIncludes;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ShowWordRequest extends FormRequest
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
                    $invalid = array_diff($values, WordDetailIncludes::ALLOWED);

                    if ($invalid !== []) {
                        $fail('The include field contains an unsupported Word detail relation.');
                    }
                },
            ],
        ];
    }

    public function includes(): WordDetailIncludes
    {
        return WordDetailIncludes::fromCsv($this->validated('include'));
    }
}
