<?php

namespace App\Application\Engagement\Actions;

use App\Http\Models\ObjectTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoadEntityHashtagsAction
{
    /**
     * Load hashtags for any entity type using the template system
     * This action demonstrates how to create generic functionality that works
     * across all entity types while maintaining efficient batch loading
     */
    public function execute(LengthAwarePaginator $entities, string $entityType): void
    {
        if ($entities->isEmpty()) {
            return;
        }

        $entityTemplateId = ObjectTemplate::where('title', $entityType)->first()->id;
        $entityIds = $entities->pluck('id')->toArray();

        $hashtags = $this->batchLoadHashtags($entityTemplateId, $entityIds);

        foreach ($entities as $entity) {
            $entity->hashtags = $hashtags[$entity->id] ?? [];
        }
    }

    /**
     * Efficiently load hashtags using two optimized queries
     * This method uses the same pattern from your original action but
     * works generically across all entity types through the template system
     */
    private function batchLoadHashtags(int $templateId, array $entityIds): array
    {
        $hashtagLinks = DB::table('hashtag_entity')
            ->where('entity_type_id', $templateId)
            ->whereIn('entity_id', $entityIds)
            ->get();

        if ($hashtagLinks->isEmpty()) {
            return [];
        }

        $uniqueTagIds = $hashtagLinks->pluck('hashtag_id')->unique();
        $uniqueTags = DB::table('uniquehashtags')
            ->whereIn('id', $uniqueTagIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($hashtagLinks as $link) {
            if (!isset($result[$link->entity_id])) {
                $result[$link->entity_id] = [];
            }

            if (isset($uniqueTags[$link->hashtag_id])) {
                $result[$link->entity_id][] = $uniqueTags[$link->hashtag_id];
            }
        }

        return $result;
    }
}
