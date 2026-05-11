<?php

namespace App\Http\v1\Catalogues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCataloguesForItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|integer',
            'types' => 'sometimes|array',
            'types.*' => 'integer',
            'search' => 'sometimes|string|min:2',
        ];
    }
}
