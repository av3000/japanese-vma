<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use App\Application\Users\Services\RoleServiceInterface;
use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Shared\Enums\UserRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(static fn (?Role $record): bool => self::isProtectedRole($record)),
                        Select::make('guard_name')
                            ->required()
                            ->default(PermissionCatalog::guardName())
                            ->options([
                                PermissionCatalog::guardName() => PermissionCatalog::guardName(),
                            ])
                            ->native(false)
                            ->disabled(static fn (?Role $record): bool => self::isProtectedRole($record)),
                    ])
                    ->columns(2),
                Section::make('Permissions')
                    ->schema(self::permissionGroupComponents())
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function permissionGroupComponents(): array
    {
        $groups = app(RoleServiceInterface::class)->getGroupedAssignablePermissions();
        $components = [];

        foreach ($groups as $groupKey => $group) {
            $components[] = Section::make($group['label'])
                ->key("permission-group-{$groupKey}")
                ->schema([
                    CheckboxList::make("permission_groups.{$groupKey}")
                        ->label('')
                        ->options($group['permissions'])
                        ->columns(2)
                        ->bulkToggleable(),
                ]);
        }

        return $components;
    }

    public static function isProtectedRole(?Role $role): bool
    {
        return $role !== null && in_array($role->name, UserRole::values(), true);
    }
}
