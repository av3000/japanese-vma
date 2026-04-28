<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Users\Models\Role as DomainRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Shared\Results\ResultError;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] ??= PermissionCatalog::guardName();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(RoleServiceInterface::class)->createRole(
            $data['name'],
            $data['guard_name'] ?? PermissionCatalog::guardName(),
            PermissionCatalog::flattenPermissionGroups($data['permission_groups'] ?? []),
        );

        if ($result->isFailure()) {
            $this->throwValidationException($result->getError());
        }

        /** @var DomainRole $role */
        $role = $result->getData();

        return Role::findByName($role->getName(), $role->getGuardName());
    }

    private function throwValidationException(ResultError $error): never
    {
        $field = $error->code === 'Roles.InvalidPermissions' ? 'permission_groups' : 'name';

        throw ValidationException::withMessages([
            $field => $error->errorMessage ?? $error->description,
        ]);
    }
}
