<?php
namespace App\Application\Engagement\Actions;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadEntityStatsAction
{
    /**
     * Make this method public so the enhancement service can use it
     */
    public function batchLoadStats(int $templateId, array $entityIds): array
    {
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

        return [
            'likes' => $likes,
            'downloads' => $downloads,
            'views' => $views,
            'comments' => $comments,
        ];
    }
}
