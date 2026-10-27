<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Changing the address clears its verification.'),
                    ])
                    ->columns(2),
                // Roles are not edited here: the Assign role and Remove role actions go through
                // RoleService so its guards apply. New accounts start with the base role.
                Section::make('Access')
                    ->schema([
                        TextEntry::make('roles_summary')
                            ->label('Roles')
                            ->state(static fn (?User $record): array => $record?->roles->pluck('name')->all() ?? [UserRole::COMMON->value])
                            ->badge()
                            ->helperText('Use the Assign role and Remove role actions to change roles.'),
                    ]),
                // Create only: staff never set another user's password, which would let a
                // `users.update` holder sign in as anyone. Edits go through UserAccountUpdateDTO.
                Section::make('Password')
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->confirmed()
                            ->minLength(8),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
