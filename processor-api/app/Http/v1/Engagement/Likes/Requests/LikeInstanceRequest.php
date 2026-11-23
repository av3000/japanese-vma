<?php

declare(strict_types=1);

namespace App\Http\v1\Engagement\Likes\Requests;

use App\Domain\Engagement\DTOs\LikeToggleDTO;
use App\Domain\Engagement\Enums\LikeTargetType;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LikeInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `template_id` is normalised before validation so a form-encoded "1" still
     * matches the integer-backed LikeTargetType - same treatment as `topic` in
     * IndexPostRequest.
     */
    protected function prepareForValidation(): void
    {
        $templateId = $this->input('template_id');

        if (is_numeric($templateId)) {
            $this->merge(['template_id' => (int) $templateId]);
        }
    }

    /**
     * The wire payload keeps `template_id` / `real_object_id` while LIKE-FE-01 is
     * outstanding: the handwritten client wrapper still posts those names.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The likeable subset of ObjectTemplateType.
            'template_id' => ['required', 'integer', Rule::enum(LikeTargetType::class)],
            'real_object_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDTO(UserId $userId): LikeToggleDTO
    {
        /** @var array{template_id: int, real_object_id: int} $validated */
        $validated = $this->validated();

        return new LikeToggleDTO(
            userId: $userId,
            target: LikeTargetType::from((int) $validated['template_id']),
            entityId: (int) $validated['real_object_id'],
        );
    }
}
