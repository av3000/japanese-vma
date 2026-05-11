<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use App\Domain\Shared\Enums\UserRole;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('guard_name'),
                        IconEntry::make('is_system_role')
                            ->state(static fn ($record): bool => in_array($record->name, UserRole::values(), true))
                            ->boolean(),
                        TextEntry::make('users_count')
                            ->label('Assigned users'),
                    ])
                    ->columns(2),
                Section::make('Permissions')
                    ->schema([
                        TextEntry::make('permissions.name')
                            ->label('')
                            ->badge(),
                    ]),
            ]);
    }
}
