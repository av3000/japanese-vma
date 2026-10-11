<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Tables;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Services\TemplateTypeClassifier;
use App\Filament\Resources\Catalogues\Actions\CatalogueModerationActions;
use App\Filament\Support\DateRangeFilter;
use App\Infrastructure\Persistence\Models\Catalogue;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CataloguesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable(['title', 'description'])
                    ->limit(50)
                    ->description(static fn (Catalogue $record): ?string => filled($record->description)
                        ? mb_strimwidth((string) $record->description, 0, 80, '…')
                        : null)
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Owner'),
                TextColumn::make('type')
                    ->badge()
                    ->color(static fn (SavedListType $state): string => (new TemplateTypeClassifier())->isKnownType($state) ? 'info' : 'gray')
                    ->formatStateUsing(static fn (SavedListType $state): string => $state->label()),
                TextColumn::make('publicity')
                    ->badge()
                    ->formatStateUsing(static fn (bool $state): string => $state ? 'Public' : 'Private')
                    ->color(static fn (bool $state): string => $state ? 'success' : 'gray')
                    ->icon(static fn (bool $state): string => $state ? 'heroicon-o-globe-alt' : 'heroicon-o-lock-closed'),
                TextColumn::make('items_count')->label('Items')->numeric()->sortable(),
                TextColumn::make('comments_count')->label('Comments')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('likes_count')->label('Likes')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Created')->since()->dateTimeTooltip()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(self::typeOptions()),
                TernaryFilter::make('publicity')
                    ->label('Public'),
                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable(),
                DateRangeFilter::make('created_at', 'Created'),
                Filter::make('empty')
                    ->label('Empty')
                    ->toggle()
                    ->query(static fn (Builder $query): Builder => $query->doesntHave('items')),
            ])
            ->recordActions([
                ViewAction::make(),
                CatalogueModerationActions::togglePublicity(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (SavedListType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
