<?php

declare(strict_types=1);

namespace App\Domain\Users\Models; // Or App\Domain\Shared\Models; if more generic

use App\Domain\Shared\Enums\UserRole;

// Making it final and readonly is a good practice for value objects/immutable domain models
final readonly class Role
{
    private function __construct(
        private string $name,
        private string $guardName, // Include guardName as it comes from Spatie
        private array $permissions = [] // Permissions might be fetched separately or embedded
    ) {}

    /**
     * Creates a Role domain model from its name and guard name.
     * Use this when you're getting role names from Spatie's getRoleNames()
     * or when you primarily only have the name and default guard.
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
     * This is typically used in the persistence layer (repository/mapper).
     */
    public static function fromSpatieRole(\Spatie\Permission\Models\Role $spatieRole): self
    {
        // Here you might fetch permissions as well if they are always needed with the role
        // For now, let's just get the name and guard.
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
