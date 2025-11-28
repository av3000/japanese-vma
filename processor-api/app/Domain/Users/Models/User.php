<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\{EntityId, UserId, Email, UserName};

class User
{
    public function __construct(
        private UserId $id,
        private EntityId $uuid,
        private UserName $name,
        private Email $email,
        private array $roles,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): UserId
    {
        return $this->id;
    }
    public function getUuid(): EntityId
    {
        return $this->uuid;
    }
    public function getName(): UserName
    {
        return $this->name;
    }
    public function getEmail(): Email
    {
        return $this->email;
    }
    public function getRoles(): array
    {
        return $this->roles;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string|UserRole $role): bool
    {
        $roleName = $role instanceof UserRole ? $role->value : $role;

        foreach ($this->roles as $userRole) {
            if ($userRole->getName() === $roleName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }
}
