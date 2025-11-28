<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Services;

use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Http\Request;

final class AuthSessionService implements AuthSessionServiceInterface
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->request->user() !== null;
    }

    public function getUserId(): ?UserId
    {
        $user = $this->request->user();

        return $user ? new UserId($user->id) : null;
    }

    public function getUserUuid(): ?string
    {
        return $this->request->user()?->uuid;
    }

    public function getTokenId(): ?string
    {
        $user = $this->request->user();

        if (!$user) {
            return null;
        }

        $token = $user->token();

        return $token?->id;
    }

    public function clear(): void
    {
        // Session clearing handled by token revocation
        // Add any additional session cleanup here if needed
    }
}
