<?php

namespace App\Http\v1\Engagement\Likes\Requests;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Foundation\Http\FormRequest;

class LikeInstanceRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                if (!ObjectTemplateType::tryFromLegacyValue((int) $value)) {
                    $fail('template_id is invalid.');
                }
            },],
            'real_object_id' => 'required|integer'
        ];
    }

    /**
     * Helper to get the Enum type value directly in the controller
     */
    public function getObjectType(): ObjectTemplateType
    {
        // Since we validated it in rules(), we can safely assume it's not null
        return ObjectTemplateType::tryFromLegacyValue((int) $this->get('template_id'));
    }
}
