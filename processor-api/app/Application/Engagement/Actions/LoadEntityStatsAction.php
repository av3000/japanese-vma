<?php
namespace App\Application\Engagement\Actions;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadEntityStatsAction
{
    /**
     * Load stats using UUIDs directly - no more complex ID mapping!
     */
    public function batchLoadStatsById(string $templateId, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        // TODO: use Repository pattern for stats
        // Simple, direct queries using UUIDs
        $likes = DB::table('likes')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $entityIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $downloads = DB::table('downloads')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $entityIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $views = DB::table('views')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $entityIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();


        $comments = DB::table('comments')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $entityIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        // Build result array indexed by entity UUID
        $result = [];
        foreach ($entityIds as $id) {
            $result[$id] = [
                'likes' => $likes[$id] ?? 0,
                'downloads' => $downloads[$id] ?? 0,
                'views' => $views[$id] ?? 0,
                'comments' => $comments[$id] ?? 0,
            ];
        }

        return $result;
    }
}
