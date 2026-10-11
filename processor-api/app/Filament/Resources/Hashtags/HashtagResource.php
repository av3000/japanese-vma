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
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Tag clean-up: usage per entity type, rename, and merge. Rename and merge run through
 * application actions; there is no create or delete.
 */
class HashtagResource extends Resource
{
    protected static ?string $model = Uniquehashtag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Community;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'content';

    /**
     * Entity types that carry hashtags, by the label shown in the usage column.
     */
    public const TAGGED_TYPES = [
        'Articles' => ObjectTemplateType::ARTICLE,
        'Posts' => ObjectTemplateType::POST,
        'Lists' => ObjectTemplateType::LIST,
    ];

    /**
     * Usage figures come from one grouped subquery over hashtag_entity joined to the page, and the
     * duplicate count from a correlated count on the normalised key, so a page is a single query.
     */
    public static function getEloquentQuery(): Builder
    {
        $usage = DB::table('hashtag_entity')
            ->select('hashtag_id')
            ->selectRaw('COUNT(*) FILTER (WHERE deleted_at IS NULL) AS active_links')
            ->selectRaw('COUNT(*) FILTER (WHERE deleted_at IS NOT NULL) AS removed_links')
            ->selectRaw('MIN(created_at) AS first_used_at')
            ->selectRaw('MAX(created_at) AS last_used_at')
            ->groupBy('hashtag_id');

        foreach (self::TAGGED_TYPES as $type) {
            $usage->selectRaw(
                'COUNT(*) FILTER (WHERE deleted_at IS NULL AND entity_type_id = ?) AS '.self::typeColumn($type),
                [$type->getLegacyId()],
            );
        }

        return parent::getEloquentQuery()
            ->leftJoinSub($usage, 'usage', 'usage.hashtag_id', '=', 'uniquehashtags.id')
            ->select('uniquehashtags.*')
            ->selectRaw('COALESCE(usage.active_links, 0) AS active_links')
            ->selectRaw('COALESCE(usage.removed_links, 0) AS removed_links')
            ->selectRaw('usage.first_used_at, usage.last_used_at')
            ->selectRaw('(SELECT COUNT(*) FROM uniquehashtags AS variants WHERE variants.normalized_content = uniquehashtags.normalized_content) AS variant_count')
            ->tap(static function (Builder $query): void {
                foreach (self::TAGGED_TYPES as $type) {
                    $column = self::typeColumn($type);
                    $query->selectRaw("COALESCE(usage.{$column}, 0) AS {$column}");
                }
            });
    }

    public static function typeColumn(ObjectTemplateType $type): string
    {
        return mb_strtolower($type->name).'_links';
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
