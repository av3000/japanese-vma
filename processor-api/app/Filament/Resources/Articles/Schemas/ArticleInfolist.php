<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

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
use Illuminate\Support\Facades\DB;

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
                                    ->state(static fn (Article $record): int => DB::table('article_kanji')->where('article_id', $record->id)->count()),
                                TextEntry::make('word_count')->label('Words')
                                    ->state(static fn (Article $record): int => DB::table('article_word')->where('article_id', $record->id)->count()),
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
        $tables = ['views' => 'Views', 'likes' => 'Likes', 'comments' => 'Comments', 'downloads' => 'Downloads'];
        $entries = [];

        foreach ($tables as $table => $label) {
            $entries[] = TextEntry::make("engagement_{$table}")
                ->label($label)
                ->state(static fn (Article $record): int => DB::table($table)
                    ->where('template_id', ObjectTemplateType::ARTICLE->getLegacyId())
                    ->where('real_object_id', $record->id)
                    ->count());
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private static function hashtags(Article $record): array
    {
        return DB::table('hashtag_entity')
            ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
            ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())
            ->where('hashtag_entity.entity_id', $record->id)
            ->whereNull('hashtag_entity.deleted_at')
            ->orderBy('uniquehashtags.content')
            ->pluck('uniquehashtags.content')
            ->all();
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
