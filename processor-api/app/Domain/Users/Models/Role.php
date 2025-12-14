<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Shared\Enums\UserRole;

final readonly class Role
{
    private function __construct(
        private string $name,
        private string $guardName,
        private array $permissions = []
    ) {}

    /**
     * Creates a Role domain model from its name and guard name.
     * Use this when you're getting role names from Spatie's getRoleNames()
     */
    public static function fromNameAndGuard(string $name, string $guardName = 'api'): self
    {
        return new self($name, $guardName);
    }

    /**
     * Creates a Role domain model from a UserRole enum.
     */
    public static function fromEnum(UserRole $role, string $guardName = 'api'): self
    {
        return new self($role->value, $guardName);
    }

    /**
     * Creates a Role domain model from a Spatie Role model.
     */
    public static function fromSpatieRole(\Spatie\Permission\Models\Role $spatieRole): self
    {
        return new self(
            name: $spatieRole->name,
            guardName: $spatieRole->guard_name,
            // permissions: $spatieRole->permissions->map(fn($p) => $p->name)->toArray() // Example if you want permissions
        );
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function getGuardName(): string
    {
        return $this->guardName;
    }

    /**
     * @return string[]
     */
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
