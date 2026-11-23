<?php

namespace App\Http\v1\Catalogues\Requests;

use App\Shared\Http\TypedResults;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCatalogueRequest extends FormRequest
{
    private const UPDATEABLE_FIELDS = [
        'title',
        'type',
        'publicity',
        'tags',
    ];

    private const EMPTY_UPDATE_MESSAGE = 'At least one field must be provided for update operation';

    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:2|max:255',
            'type' => 'sometimes|integer|in:5,6,7,8,9',
            'publicity' => 'sometimes|boolean',
            'tags' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'The list title must be at least 2 characters',
            'title.max' => 'The list title must not exceed 255 characters',
            'type.in' => 'List type must be one of 5, 6, 7, 8, or 9 for custom lists',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            if ($validator->errors()->isNotEmpty() || $this->hasAnyUpdateableFields()) {
                return;
            }

            throw new HttpResponseException(
                TypedResults::validationProblem(
                    ['fields' => [self::EMPTY_UPDATE_MESSAGE]],
                    'No fields to update',
                )
            );
        });
    }

    private function hasAnyUpdateableFields(): bool
    {
        return collect(self::UPDATEABLE_FIELDS)
            ->some(fn (string $field): bool => $this->exists($field));
    }
}
