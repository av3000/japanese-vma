<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Requests;

use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => ['nullable', 'uuid'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'string'], // Assuming role names return strings
            'include_inactive' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:' . Pagination::MIN_PAGE],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . Pagination::MAX_PER_PAGE],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->input('page', Pagination::MIN_PAGE),
            'per_page' => $this->input('per_page', Pagination::DEFAULT_PER_PAGE),
        ]);
    }
}
