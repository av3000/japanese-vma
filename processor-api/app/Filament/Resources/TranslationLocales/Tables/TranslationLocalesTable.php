<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Tables;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Domain\Translations\Models\TranslationLocale as DomainTranslationLocale;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TranslationLocalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(static fn (TranslationLocale $record): bool => $record->code === DomainTranslationLocale::FALLBACK_CODE)
                    ->updateStateUsing(static function (TranslationLocale $record, bool $state): bool {
                        $result = app(TranslationLocaleAdminServiceInterface::class)->update(
                            $record->code,
                            $record->name,
                            $state,
                            PanelActor::current(),
                        );

                        if ($result->isFailure()) {
                            ResultNotifications::failure($result);

                            return (bool) $record->is_active;
                        }

                        return $state;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
