<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User details')
                    ->schema([
                        Hidden::make('uuid')
                            ->default(static fn (): string => (string) Str::uuid()),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->seconds(false),
                    ])
                    ->columns(2),
                Section::make('Access')
                    ->schema([
                        Select::make('roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('guard_name', 'api'),
                            )
                            ->multiple()
                            ->preload()
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Password')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(static fn (string $operation): bool => $operation === 'create')
                            ->confirmed()
                            ->dehydrateStateUsing(static fn (string $state): string => Hash::make($state))
                            ->dehydrated(static fn (?string $state): bool => filled($state))
                            ->minLength(8),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(static fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),
            ]);
    }
}
