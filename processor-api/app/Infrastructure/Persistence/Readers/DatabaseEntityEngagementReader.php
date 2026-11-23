<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Engagement\Interfaces\Readers\EntityEngagementReaderInterface;
use App\Domain\Engagement\DTOs\EntityEngagementCountsDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Support\Facades\DB;

final class DatabaseEntityEngagementReader implements EntityEngagementReaderInterface
{
    private const COUNTED_TABLES = ['views', 'likes', 'comments', 'downloads'];

    public function counts(ObjectTemplateType $type, int $entityId): EntityEngagementCountsDTO
    {
        $query = DB::query();

        foreach (self::COUNTED_TABLES as $table) {
            $query->selectSub(
                DB::table($table)
                    ->selectRaw('COUNT(*)')
                    ->where('template_id', $type->getLegacyId())
                    ->where('real_object_id', $entityId),
                $table,
            );
        }

        $row = $query->first();

        return new EntityEngagementCountsDTO(
            views: (int) $row->views,
            likes: (int) $row->likes,
            comments: (int) $row->comments,
            downloads: (int) $row->downloads,
        );
    }

    public function hashtagNames(ObjectTemplateType $type, int $entityId): array
    {
        return DB::table('hashtag_entity')
            ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
            ->where('hashtag_entity.entity_type_id', $type->getLegacyId())
            ->where('hashtag_entity.entity_id', $entityId)
            ->whereNull('hashtag_entity.deleted_at')
            ->orderBy('uniquehashtags.content')
            ->pluck('uniquehashtags.content')
            ->map(static fn (mixed $content): string => (string) $content)
            ->values()
            ->all();
    }
}
