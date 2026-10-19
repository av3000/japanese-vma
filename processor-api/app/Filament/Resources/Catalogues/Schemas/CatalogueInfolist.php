<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Schemas;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Filament\Support\ClientUrls;
use App\Infrastructure\Persistence\Models\Catalogue;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class CatalogueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Catalogue')
                    ->schema([
                        TextEntry::make('title')->columnSpan(2),
                        TextEntry::make('user.name')->label('Owner'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(static fn (SavedListType $state): string => $state->label()),
                        IconEntry::make('publicity')->label('Public')->boolean(),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'white-space: pre-line']),
                        TextEntry::make('hashtags')
                            ->badge()
                            ->state(static fn (Catalogue $record): array => DB::table('hashtag_entity')
                                ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
                                ->where('hashtag_entity.entity_type_id', ObjectTemplateType::LIST->getLegacyId())
                                ->where('hashtag_entity.entity_id', $record->id)
                                ->whereNull('hashtag_entity.deleted_at')
                                ->orderBy('uniquehashtags.content')
                                ->pluck('uniquehashtags.content')
                                ->all())
                            ->placeholder('No hashtags'),
                        TextEntry::make('items_count')->label('Items'),
                        TextEntry::make('comments_count')->label('Comments'),
                        TextEntry::make('likes_count')->label('Likes'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('uuid')
                            ->label('UUID')
                            ->copyable()
                            ->url(static fn (Catalogue $record): ?string => ClientUrls::forEntity(ObjectTemplateType::LIST, (string) $record->uuid))
                            ->openUrlInNewTab(),
                        TextEntry::make('id')->label('Legacy id (support lookups)'),
                    ])
                    ->columns(4),
            ]);
    }
}
