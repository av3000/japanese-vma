<?php

namespace App\Application\Engagement\Services;
// use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Articles\Models\Articles;

interface EntityEnhancementServiceInterface
{
    // public function enhanceWithStats(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    public function enhanceArticlesWithStats(Articles $articles): Articles;
    // public function enhanceWithHashtags(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    // public function enhanceWithOptions(LengthAwarePaginator $entities,string $entityType, bool $include_stats = false, bool $include_hashtags = false): LengthAwarePaginator;
}
