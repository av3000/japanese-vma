<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Sentences\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSentenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'min:4', 'max:300']];
    }
}
