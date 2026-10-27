<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Application\Engagement\Interfaces\Readers\EntityEngagementReaderInterface;
use App\Domain\Engagement\DTOs\EntityEngagementCountsDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Filament\Support\StatusBadges;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article')
                    ->schema([
                        TextEntry::make('title_jp')->label('Title (Japanese)')->columnSpanFull(),
                        TextEntry::make('title_en')->label('Title (English)')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('user.name')->label('Author'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(static fn (ArticleStatus $state): string => $state->label())
                            ->color(static fn (ArticleStatus $state): string => StatusBadges::articleColor($state)),
                        TextEntry::make('publicity')
                            ->badge()
                            ->formatStateUsing(static fn (PublicityStatus $state): string => $state->label())
                            ->color(static fn (PublicityStatus $state): string => StatusBadges::publicityColor($state)),
                        TextEntry::make('source_link')
                            ->label('Source')
                            ->url(static fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('-'),
                        TextEntry::make('hashtags')
                            ->badge()
                            ->state(static fn (Article $record): array => self::hashtags($record))
                            ->placeholder('No hashtags'),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                        TextEntry::make('uuid')->label('UUID')->copyable(),
                        TextEntry::make('id')->label('Legacy id'),
                    ])
                    ->columns(3),
                Grid::make(2)
                    ->schema([
                        Section::make('Engagement')
                            ->schema(self::engagementEntries())
                            ->columns(2),
                        Section::make('Study data')
                            ->schema([
                                TextEntry::make('kanji_count')->label('Kanji')
                                    ->state(static fn (Article $record): int => $record->kanjis()->count()),
                                TextEntry::make('word_count')->label('Words')
                                    ->state(static fn (Article $record): int => $record->words()->count()),
                                TextEntry::make('jlpt')->label('JLPT kanji')
                                    ->state(static fn (Article $record): string => sprintf(
                                        'N5 %d · N4 %d · N3 %d · N2 %d · N1 %d · other %d',
                                        $record->n5, $record->n4, $record->n3, $record->n2, $record->n1, $record->uncommon,
                                    ))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
                Section::make('Processing')
                    ->schema([
                        TextEntry::make('processing.status')
                            ->label('Status')
                            ->badge()
                            ->state(static fn (Article $record): ?ProcessingStatus => self::processing($record)?->status)
                            ->formatStateUsing(static fn (?ProcessingStatus $state): string => StatusBadges::processingLabel($state))
                            ->color(static fn (?ProcessingStatus $state): string => StatusBadges::processingColor($state))
                            ->placeholder('Never processed'),
                        TextEntry::make('processing.attempt')
                            ->label('Attempt')
                            ->state(static fn (Article $record): ?string => ($row = self::processing($record)) === null ? null : "{$row->attempt} of {$row->max_attempts}")
                            ->placeholder('-'),
                        TextEntry::make('processing.started_at')->label('Started')
                            ->state(static fn (Article $record) => self::processing($record)?->started_at)
                            ->dateTime()->placeholder('-'),
                        TextEntry::make('processing.finished_at')->label('Finished')
                            ->state(static fn (Article $record) => self::processing($record)?->finished_at)
                            ->dateTime()->placeholder('-'),
                        TextEntry::make('processing.error')->label('Error')
                            ->state(static fn (Article $record): ?string => ($row = self::processing($record)) === null || $row->error_message === null
                                ? null
                                : trim(($row->error_code ?? '').' '.$row->error_message))
                            ->placeholder('None')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Content')
                    ->schema([
                        // Plain text: article bodies are user input and never rendered as HTML.
                        TextEntry::make('content_jp')->label('Japanese')->columnSpanFull()->extraAttributes(['style' => 'white-space: pre-line']),
                        TextEntry::make('content_en')->label('English')->placeholder('-')->columnSpanFull()->extraAttributes(['style' => 'white-space: pre-line']),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * @return list<TextEntry>
     */
    private static function engagementEntries(): array
    {
        return [
            TextEntry::make('engagement_views')->label('Views')
                ->state(static fn (Article $record): int => self::engagement($record)->views),
            TextEntry::make('engagement_likes')->label('Likes')
                ->state(static fn (Article $record): int => self::engagement($record)->likes),
            TextEntry::make('engagement_comments')->label('Comments')
                ->state(static fn (Article $record): int => self::engagement($record)->comments),
            TextEntry::make('engagement_downloads')->label('Downloads')
                ->state(static fn (Article $record): int => self::engagement($record)->downloads),
        ];
    }

    /**
     * The four engagement counts, read in one query per article per render and kept on the record.
     */
    private static function engagement(Article $record): EntityEngagementCountsDTO
    {
        if (! $record->relationLoaded('adminEngagement')) {
            $record->setRelation('adminEngagement', app(EntityEngagementReaderInterface::class)->counts(ObjectTemplateType::ARTICLE, (int) $record->id));
        }

        return $record->getRelation('adminEngagement');
    }

    /**
     * @return list<string>
     */
    private static function hashtags(Article $record): array
    {
        return app(EntityEngagementReaderInterface::class)->hashtagNames(ObjectTemplateType::ARTICLE, (int) $record->id);
    }

    /**
     * The processing row, read once per article per render and kept on the record.
     */
    private static function processing(Article $record): ?ProcessingState
    {
        if (! $record->relationLoaded('adminProcessingState')) {
            $record->setRelation('adminProcessingState', ProcessingState::query()
                ->where('entity_type', ProcessingEntityType::Article->value)
                ->where('entity_id', $record->uuid)
                ->where('task_type', ProcessingTaskType::ArticleContentProcessing->value)
                ->first());
        }

        return $record->getRelation('adminProcessingState');
    }
}
