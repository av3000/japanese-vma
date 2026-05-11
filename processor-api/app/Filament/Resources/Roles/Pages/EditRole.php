<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Users\Models\Role as DomainRole;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $record */
        $record = $this->getRecord()->loadMissing('permissions');
        $data['permission_groups'] = PermissionCatalog::permissionGroupState(
            $record->permissions->pluck('name')->all(),
        );

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        $result = app(RoleServiceInterface::class)->updateRole(
            $record->name,
            $data['name'],
            $data['guard_name'] ?? PermissionCatalog::guardName(),
            PermissionCatalog::flattenPermissionGroups($data['permission_groups'] ?? []),
        );

        if ($result->isFailure()) {
            $field = $result->getError()->code === 'Roles.InvalidPermissions' ? 'permission_groups' : 'name';

            throw ValidationException::withMessages([
                $field => $result->getError()->errorMessage ?? $result->getError()->description,
            ]);
        }

        /** @var DomainRole $updatedRole */
        $updatedRole = $result->getData();

        return Role::findByName($updatedRole->getName(), $updatedRole->getGuardName());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => static::getResource()::canDelete($this->getRecord()))
                ->action(function (): void {
                    /** @var Role $record */
                    $record = $this->getRecord();
                    $result = app(RoleServiceInterface::class)->deleteRole($record->name);

                    if ($result->isFailure()) {
                        Notification::make()
                            ->danger()
                            ->title($result->getError()->errorMessage ?? $result->getError()->description)
                            ->send();

                        return;
                    }

                    $this->redirect(RoleResource::getUrl('index'));
                }),
        ];
    }
}
