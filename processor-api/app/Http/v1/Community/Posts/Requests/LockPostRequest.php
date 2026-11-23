<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Explicit lock state rather than the legacy toggle.
 *
 * Legacy exposed the same toggle twice (`post/{id}/toggleLock` unguarded, and an
 * admin-guarded `post/{id}/togglelock`); a retried toggle silently flips the Post
 * back. Sending the desired state makes the endpoint idempotent and lets the
 * client render the control from data instead of inferring it.
 */
class LockPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locked' => ['required', 'boolean'],
        ];
    }
}
