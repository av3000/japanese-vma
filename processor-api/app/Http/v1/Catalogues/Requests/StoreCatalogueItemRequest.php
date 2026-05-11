<?php

namespace App\Http\v1\Catalogues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogueItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|integer|min:1',
        ];
    }
}
