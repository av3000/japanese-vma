<?php

namespace App\Http\v1\Comments\Requests;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', Rule::enum(ObjectTemplateType::class)],
            'entity_id' => ['required', 'integer', 'min:1'],
            'entity_uuid' => 'required|string|uuid',
            'content' => 'required|string|min:2|max:1000',
            'parent_comment_id' => 'sometimes|integer|min:1',
        ];
    }
}
