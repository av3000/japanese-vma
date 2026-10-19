<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Filament\Resources\Articles\Actions\ArticleModerationActions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\DateRangeFilter;
use App\Filament\Support\StatusBadges;
use App\Infrastructure\Persistence\Models\Article;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ArticlesTable
{
    public const NEVER_PROCESSED = 'never';

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query
                ->select(ArticleResource::LIST_COLUMNS)
                ->addSelect(ArticleResource::derivedColumns()))
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('title_jp')
                    ->label('Title')
                    ->searchable(['title_jp', 'title_en'])
                    ->limit(60)
                    ->description(static fn (Article $record): ?string => $record->title_en)
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Author'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (ArticleStatus $state): string => $state->label())
                    ->color(static fn (ArticleStatus $state): string => StatusBadges::articleColor($state))
                    ->sortable(),
                TextColumn::make('publicity')
                    ->badge()
                    ->formatStateUsing(static fn (PublicityStatus $state): string => $state->label())
                    ->color(static fn (PublicityStatus $state): string => StatusBadges::publicityColor($state))
                    ->icon(static fn (PublicityStatus $state) => StatusBadges::publicityIcon($state)),
                TextColumn::make('processing_status')
                    ->label('Processing')
                    ->badge()
                    ->state(static fn (Article $record): ?ProcessingStatus => ProcessingStatus::tryFrom((string) $record->getAttribute('processing_status')))
                    ->formatStateUsing(static fn (?ProcessingStatus $state): string => StatusBadges::processingLabel($state))
                    ->color(static fn (?ProcessingStatus $state): string => StatusBadges::processingColor($state))
                    ->placeholder('Never processed'),
                TextColumn::make('jlpt')
                    ->label('JLPT kanji')
                    ->state(static fn (Article $record): string => sprintf(
                        'N5 %d · N4 %d · N3 %d · N2 %d · N1 %d',
                        $record->n5, $record->n4, $record->n3, $record->n2, $record->n1,
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hashtags_count')
                    ->label('Tags')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(self::statusOptions())
                    ->default(array_map('strval', ArticleStatus::awaitingReviewValues())),
                SelectFilter::make('publicity')
                    ->options([
                        PublicityStatus::PUBLIC->value => PublicityStatus::PUBLIC->label(),
                        PublicityStatus::PRIVATE->value => PublicityStatus::PRIVATE->label(),
                    ]),
                SelectFilter::make('processing')
                    ->label('Processing')
                    ->options([
                        ...array_combine(
                            array_map(static fn (ProcessingStatus $status): string => $status->value, ProcessingStatus::cases()),
                            array_map(static fn (ProcessingStatus $status): string => StatusBadges::processingLabel($status), ProcessingStatus::cases()),
                        ),
                        self::NEVER_PROCESSED => 'Never processed',
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => self::filterByProcessing($query, $data['value'] ?? null)),
                SelectFilter::make('user_id')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable(),
                DateRangeFilter::make('created_at', 'Submitted'),
                Filter::make('hashtag')
                    ->schema([
                        TextInput::make('tag')->label('Hashtag contains'),
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => self::filterByHashtag($query, $data['tag'] ?? null))
                    ->indicateUsing(static fn (array $data): ?string => filled($data['tag'] ?? null) ? "Hashtag: {$data['tag']}" : null),
            ])
            ->recordActions([
                ViewAction::make(),
                ArticleModerationActions::setStatus(),
            ])
            ->toolbarActions([
                ArticleModerationActions::bulkSetStatus(),
            ]);
    }

    private static function filterByProcessing(Builder $query, ?string $value): Builder
    {
        if ($value === null || $value === '') {
            return $query;
        }

        $states = static fn (QueryBuilder $states): QueryBuilder => $states
            ->from('processing_states')
            ->whereColumn('processing_states.entity_id', 'articles.uuid')
            ->where('processing_states.entity_type', 'article');

        if ($value === self::NEVER_PROCESSED) {
            return $query->whereNotExists($states);
        }

        return $query->whereExists(static fn (QueryBuilder $states_) => $states($states_)->where('processing_states.status', $value));
    }

    private static function filterByHashtag(Builder $query, ?string $tag): Builder
    {
        if ($tag === null || trim($tag) === '') {
            return $query;
        }

        return $query->whereExists(static fn (QueryBuilder $links): QueryBuilder => $links
            ->from('hashtag_entity')
            ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
            ->whereColumn('hashtag_entity.entity_id', 'articles.id')
            ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())
            ->whereNull('hashtag_entity.deleted_at')
            ->where('uniquehashtags.content', 'ILIKE', '%'.addcslashes(trim($tag), '%_\\').'%'));
    }

    /**
     * @return array<int, string>
     */
    private static function statusOptions(): array
    {
        $options = [];

        foreach (ArticleStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
