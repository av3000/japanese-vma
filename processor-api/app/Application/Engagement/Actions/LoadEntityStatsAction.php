<?php
namespace App\Application\Engagement\Actions;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadEntityStatsAction
{
    /**
     * Load stats using UUIDs directly - no more complex ID mapping!
     */
    public function batchLoadStatsByUuid(string $entityTypeUuid, array $entityUuids): array
    {
        if (empty($entityUuids)) {
            return [];
        }

        // TODO: use Repository pattern for stats
        // Simple, direct queries using UUIDs
        $likes = DB::table('likes')
            ->where('entity_type_uuid', $entityTypeUuid)
            ->whereIn('real_object_uuid', $entityUuids)
            ->groupBy('real_object_uuid')
            ->pluck(DB::raw('count(*)'), 'real_object_uuid')
            ->toArray();

        $downloads = DB::table('downloads')
            ->where('entity_type_uuid', $entityTypeUuid)
            ->whereIn('real_object_uuid', $entityUuids)
            ->groupBy('real_object_uuid')
            ->pluck(DB::raw('count(*)'), 'real_object_uuid')
            ->toArray();

        $views = DB::table('views')
            ->where('entity_type_uuid', $entityTypeUuid)
            ->whereIn('real_object_uuid', $entityUuids)
            ->groupBy('real_object_uuid')
            ->pluck(DB::raw('count(*)'), 'real_object_uuid')
            ->toArray();

        $comments = DB::table('comments')
            ->where('entity_type_uuid', $entityTypeUuid)
            ->whereIn('real_object_uuid', $entityUuids)
            ->groupBy('real_object_uuid')
            ->pluck(DB::raw('count(*)'), 'real_object_uuid')
            ->toArray();

        // Build result array indexed by entity UUID
        $result = [];
        foreach ($entityUuids as $uuid) {
            $result[$uuid] = [
                'likes' => $likes[$uuid] ?? 0,
                'downloads' => $downloads[$uuid] ?? 0,
                'views' => $views[$uuid] ?? 0,
                'comments' => $comments[$uuid] ?? 0,
            ];
        }

        return $result;
    }

    // /**
    //  * Make this method public so the enhancement service can use it
    //  */
    // public function batchLoadStats(int $templateId, array $entityIds): array
    // {
    //     $likes = DB::table('likes')
    //         ->where('template_id', $templateId)
    //         ->whereIn('real_object_id', $entityIds)
    //         ->groupBy('real_object_id')
    //         ->pluck(DB::raw('count(*)'), 'real_object_id')
    //         ->toArray();

    //     $downloads = DB::table('downloads')
    //         ->where('template_id', $templateId)
    //         ->whereIn('real_object_id', $entityIds)
    //         ->groupBy('real_object_id')
    //         ->pluck(DB::raw('count(*)'), 'real_object_id')
    //         ->toArray();

    //     $views = DB::table('views')
    //         ->where('template_id', $templateId)
    //         ->whereIn('real_object_id', $entityIds)
    //         ->groupBy('real_object_id')
    //         ->pluck(DB::raw('count(*)'), 'real_object_id')
    //         ->toArray();

    //     $comments = DB::table('comments')
    //         ->where('template_id', $templateId)
    //         ->whereIn('real_object_id', $entityIds)
    //         ->groupBy('real_object_id')
    //         ->pluck(DB::raw('count(*)'), 'real_object_id')
    //         ->toArray();

    //     return [
    //         'likes' => $likes,
    //         'downloads' => $downloads,
    //         'views' => $views,
    //         'comments' => $comments,
    //     ];
    // }
}
