<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Users\Models\Role as DomainRole;
use App\Filament\Resources\Roles\Pages\Concerns\ReportsRoleServiceErrors;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    use ReportsRoleServiceErrors;

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
            $this->reportRoleServiceError($result->getError());
        }

        /** @var DomainRole $role */
        $role = $result->getData();

        return Role::findByName($role->getName(), $role->getGuardName());
    }
}
