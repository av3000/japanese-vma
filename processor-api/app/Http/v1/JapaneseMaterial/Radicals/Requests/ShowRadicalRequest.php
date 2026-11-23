<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Requests;

use App\Domain\JapaneseMaterial\Radicals\DTOs\RadicalDetailIncludes;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ShowRadicalRequest extends FormRequest
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
                    $invalid = array_diff($values, RadicalDetailIncludes::ALLOWED);

                    if ($invalid !== []) {
                        $fail('The include field contains an unsupported Radical detail relation.');
                    }
                },
            ],
        ];
    }

    public function includes(): RadicalDetailIncludes
    {
        return RadicalDetailIncludes::fromCsv($this->validated('include'));
    }
}
