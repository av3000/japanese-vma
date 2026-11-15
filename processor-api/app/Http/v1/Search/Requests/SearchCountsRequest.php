<?php

declare(strict_types=1);

namespace App\Http\v1\Search\Requests;

use App\Domain\Shared\ValueObjects\SearchTerm;
use Illuminate\Foundation\Http\FormRequest;

class SearchCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The global TrimStrings middleware has already trimmed `q`, and turned a blank one
     * into null, so `required` also rejects whitespace.
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:'.SearchTerm::MAX_LENGTH],
        ];
    }

    public function keyword(): string
    {
        return (string) $this->validated('q');
    }
}
