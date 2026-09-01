<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Providers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Users\Models\User as DomainUser;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Infrastructure\Persistence\Repositories\UserMapper;
use Illuminate\Http\Request;
use LogicException;

final class PassportCurrentUserProvider implements CurrentUserProviderInterface
{
    private bool $principalResolved = false;

    private ?PersistenceUser $principal = null;

    private bool $authenticatedUserResolved = false;

    private ?AuthenticatedUser $authenticatedUser = null;

    private bool $userResolved = false;

    private ?DomainUser $user = null;

    public function __construct(
        private readonly Request $request,
        private readonly UserMapper $userMapper,
    ) {
    }

    public function currentAuthenticatedUser(): ?AuthenticatedUser
    {
        if ($this->authenticatedUserResolved) {
            return $this->authenticatedUser;
        }

        $this->authenticatedUserResolved = true;
        $user = $this->currentPrincipal();

        if ($user === null) {
            return null;
        }

        return $this->authenticatedUser = new AuthenticatedUser(
            id: new UserId((int) $user->id),
            uuid: new EntityId((string) $user->uuid),
            name: new UserName((string) $user->name),
            isAdmin: $this->isAdmin($user),
        );
    }

    public function currentUser(): ?DomainUser
    {
        if ($this->userResolved) {
            return $this->user;
        }

        $this->userResolved = true;
        $user = $this->currentPrincipal();

        return $this->user = $user === null
            ? null
            : $this->userMapper->mapToDomain($user);
    }

    public function currentAccessTokenId(): ?string
    {
        $tokenId = $this->currentPrincipal()?->token()?->getKey();

        return $tokenId === null ? null : (string) $tokenId;
    }

    private function currentPrincipal(): ?PersistenceUser
    {
        if ($this->principalResolved) {
            return $this->principal;
        }

        $this->principalResolved = true;
        $principal = $this->request->user('api');

        if ($principal !== null && ! $principal instanceof PersistenceUser) {
            throw new LogicException('The api guard must resolve a persistence User.');
        }

        return $this->principal = $principal;
    }

    private function isAdmin(PersistenceUser $user): bool
    {
        if ($user->relationLoaded('roles')) {
            return $user->roles->contains(
                static fn ($role): bool => $role->name === UserRole::ADMIN->value,
            );
        }

        return $user->hasRole(UserRole::ADMIN->value);
    }
}
