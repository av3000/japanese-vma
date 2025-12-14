<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Services;

use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Repositories\UserMapper;
use App\Domain\Users\Models\User as DomainUser;
use Illuminate\Http\Request;

final class AuthSessionService implements AuthSessionServiceInterface
{
    private ?DomainUser $cachedDomainUser = null;

    public function __construct(
        private readonly Request $request,
        private readonly UserMapper $userMapper,
    ) {}

    public function isAuthenticated(): bool
    {
        dd($this->request->user());
        return $this->request->user() !== null;
    }

    public function getAuthenticatedDomainUser(): ?DomainUser
    {
        if ($this->cachedDomainUser !== null) {
            return $this->cachedDomainUser;
        }

        $persistenceUser = $this->request->user();
        if (!$persistenceUser) {
            return null;
        }

        return $this->cachedDomainUser = $this->userMapper->mapToDomain($persistenceUser);
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
