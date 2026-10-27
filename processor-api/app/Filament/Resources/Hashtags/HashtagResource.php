<?php

declare(strict_types=1);

namespace App\Filament\Resources\Hashtags;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Hashtags\Pages\ListHashtags;
use App\Filament\Resources\Hashtags\Tables\HashtagsTable;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Tag clean-up: usage per entity type, rename, and merge. Rename and merge run through
 * application actions; there is no create or delete.
 *
 * @extends Resource<Uniquehashtag>
 */
class HashtagResource extends Resource
{
    protected static ?string $model = Uniquehashtag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Community;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'content';

    protected static ?string $navigationLabel = 'Hashtags';

    protected static ?string $modelLabel = 'hashtag';

    /**
     * Entity types that carry hashtags, by the label shown in the usage column.
     */
    public const TAGGED_TYPES = [
        'Articles' => ObjectTemplateType::ARTICLE,
        'Posts' => ObjectTemplateType::POST,
        'Lists' => ObjectTemplateType::LIST,
    ];

    /**
     * Usage figures and the variant count for a page of tags, in one query (Uniquehashtag::withUsage).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withUsage(array_values(self::TAGGED_TYPES));
    }

    public static function table(Table $table): Table
    {
        return HashtagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHashtags::route('/'),
        ];
    }
}
