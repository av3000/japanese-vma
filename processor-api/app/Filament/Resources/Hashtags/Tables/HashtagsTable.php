<?php

declare(strict_types=1);

namespace App\Filament\Resources\Hashtags\Tables;

use App\Application\Engagement\Actions\MergeHashtagsAction;
use App\Application\Engagement\Actions\RenameHashtagAction;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Engagement\DTOs\HashtagMergeResultDTO;
use App\Domain\Engagement\ValueObjects\HashtagName;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Hashtags\HashtagResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HashtagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('active_links', 'desc')
            ->columns([
                TextColumn::make('content')
                    ->label('Tag')
                    ->searchable(query: static function (Builder $query, string $search): Builder {
                        /** @var Builder<Uniquehashtag> $query */
                        return $query->matching($search);
                    }),
                TextColumn::make('active_links')
                    ->label('In use')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('by_type')
                    ->label('By type')
                    ->state(static fn (Uniquehashtag $record): string => implode(' · ', array_map(
                        static fn (string $label, $type): string => $label.' '.(int) $record->getAttribute(Uniquehashtag::usageColumn($type)),
                        array_keys(HashtagResource::TAGGED_TYPES),
                        HashtagResource::TAGGED_TYPES,
                    ))),
                TextColumn::make('removed_links')
                    ->label('Removed links')
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('variant_count')
                    ->label('Variants')
                    ->badge()
                    ->state(static fn (Uniquehashtag $record): ?string => (int) $record->getAttribute('variant_count') > 1
                        ? ((int) $record->getAttribute('variant_count')).' variants'
                        : null)
                    ->color('warning')
                    ->icon(Heroicon::OutlinedSquare2Stack)
                    ->placeholder('-'),
                TextColumn::make('first_used_at')
                    ->label('First used')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_used_at')
                    ->label('Last used')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('entity_type')
                    ->label('Used on')
                    ->options(array_combine(
                        array_map(static fn (ObjectTemplateType $type): string => $type->value, HashtagResource::TAGGED_TYPES),
                        array_keys(HashtagResource::TAGGED_TYPES),
                    ))
                    ->query(static function (Builder $query, array $data): Builder {
                        /** @var Builder<Uniquehashtag> $query */
                        $type = ObjectTemplateType::tryFrom((string) ($data['value'] ?? ''));

                        return $type === null ? $query : $query->usedOn($type);
                    }),
                Filter::make('unused')
                    ->label('Unused')
                    ->toggle()
                    ->query(static function (Builder $query): Builder {
                        /** @var Builder<Uniquehashtag> $query */
                        return $query->unused();
                    }),
                Filter::make('minimum_usage')
                    ->schema([
                        TextInput::make('minimum')->label('Used at least')->numeric()->minValue(1),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        /** @var Builder<Uniquehashtag> $query */
                        return filled($data['minimum'] ?? null) ? $query->usedAtLeast((int) $data['minimum']) : $query;
                    })
                    ->indicateUsing(static fn (array $data): ?string => filled($data['minimum'] ?? null) ? "Used at least {$data['minimum']} times" : null),
                Filter::make('has_duplicates')
                    ->label('Has case or width variants')
                    ->toggle()
                    ->query(static fn (Builder $query): Builder => $query->whereIn(
                        'uniquehashtags.normalized_content',
                        Uniquehashtag::duplicateKeys(),
                    )),
            ])
            ->recordActions([
                self::renameAction(),
                self::mergeAction(),
            ])
            ->toolbarActions([
                self::bulkMergeAction(),
            ]);
    }

    private static function renameAction(): Action
    {
        return Action::make('rename')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->visible(static fn (): bool => PanelActor::may(PanelPermission::HashtagsManage))
            ->fillForm(static fn (Uniquehashtag $record): array => ['name' => $record->content])
            ->schema([
                TextInput::make('name')
                    ->label('New name')
                    ->required()
                    ->maxLength(HashtagName::MAX_LENGTH),
            ])
            ->modalDescription('Every article, post and list using this tag shows the new name.')
            ->action(static function (Uniquehashtag $record, array $data): void {
                $result = app(RenameHashtagAction::class)->execute((int) $record->id, (string) $data['name'], PanelActor::current());

                ResultNotifications::send($result, 'Hashtag renamed.');
            });
    }

    private static function mergeAction(): Action
    {
        return Action::make('merge')
            ->label('Merge into…')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('gray')
            ->visible(static fn (): bool => PanelActor::may(PanelPermission::HashtagsManage))
            ->fillForm(static fn (Uniquehashtag $record): array => ['target' => self::defaultTargetFor($record)])
            ->schema(static fn (Uniquehashtag $record): array => [
                Select::make('target')
                    ->label('Merge this tag into')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(static fn (string $search): array => Uniquehashtag::query()
                        ->where('id', '!=', $record->id)
                        ->where('content', 'ILIKE', '%'.addcslashes($search, '%_\\').'%')
                        ->orderBy('content')
                        ->limit(25)
                        ->pluck('content', 'id')
                        ->all())
                    ->getOptionLabelUsing(static fn ($value): ?string => Uniquehashtag::query()->whereKey($value)->value('content'))
                    ->options(static fn (): array => Uniquehashtag::query()
                        ->where('normalized_content', $record->normalized_content)
                        ->where('id', '!=', $record->id)
                        ->pluck('content', 'id')
                        ->all()),
            ])
            ->modalDescription(static fn (Uniquehashtag $record): string => "Every link to \"{$record->content}\" moves to the chosen tag and \"{$record->content}\" is deleted. Entities tagged with both keep one link.")
            ->action(static function (Uniquehashtag $record, array $data): void {
                self::notifyMerge(app(MergeHashtagsAction::class)->execute((int) $record->id, (int) $data['target'], PanelActor::current()));
            });
    }

    private static function bulkMergeAction(): BulkAction
    {
        return BulkAction::make('mergeSelected')
            ->label('Merge selected')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->visible(static fn (): bool => PanelActor::may(PanelPermission::HashtagsManage))
            ->fillForm(static fn (Collection $records): array => ['target' => $records->sortByDesc('active_links')->first()?->id])
            ->schema(static fn (Collection $records): array => [
                Select::make('target')
                    ->label('Keep this tag')
                    ->helperText('Defaults to the most used one. The others are merged into it.')
                    ->required()
                    ->options($records->mapWithKeys(static fn (Uniquehashtag $tag): array => [
                        $tag->id => "{$tag->content} ({$tag->getAttribute('active_links')} uses)",
                    ])->all()),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(static function (Collection $records, array $data): void {
                $targetId = (int) $data['target'];
                $failures = [];
                $sources = $records->reject(static fn (Uniquehashtag $tag): bool => (int) $tag->id === $targetId);

                foreach ($sources as $source) {
                    $result = app(MergeHashtagsAction::class)->execute((int) $source->id, $targetId, PanelActor::current());

                    if ($result->isFailure()) {
                        $failures[] = $source->content.': '.ResultNotifications::message($result);
                    }
                }

                ResultNotifications::bulk($sources->count(), $failures, 'merged');
            });
    }

    private static function notifyMerge(Result $result): void
    {
        if ($result->isFailure()) {
            ResultNotifications::failure($result);

            return;
        }

        /** @var HashtagMergeResultDTO $merged */
        $merged = $result->getData();

        Notification::make()
            ->success()
            ->title("Merged \"{$merged->source->content}\" into \"{$merged->target->content}\".")
            ->body(sprintf(
                '%d link(s) moved, %d duplicate link(s) retired.',
                $merged->activeLinksMoved(),
                count($merged->retiredLinkIds),
            ))
            ->send();
    }

    /**
     * The most used other variant of the same tag, if there is one.
     */
    private static function defaultTargetFor(Uniquehashtag $record): ?int
    {
        $variant = HashtagResource::getEloquentQuery()
            ->where('uniquehashtags.normalized_content', $record->normalized_content)
            ->where('uniquehashtags.id', '!=', $record->id)
            ->reorder()
            ->orderByDesc('active_links')
            ->first();

        return $variant === null ? null : (int) $variant->id;
    }
}
