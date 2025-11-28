<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

use App\Domain\Shared\Enums\UserRole;

final readonly class RoleDTO
{
    private function __construct(
        private string $name,
        private array $permissions = []
    ) {}

    public static function fromName(string $name): self
    {
        return new self($name);
    }

    public static function fromEnum(UserRole $role): self
    {
        return new self($role->value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function toEnum(): ?UserRole
    {
        return UserRole::tryFrom($this->name);
    }

    public function isAdmin(): bool
    {
        return $this->name === UserRole::ADMIN->value;
    }
}
