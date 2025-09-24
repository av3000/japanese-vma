<?php

namespace App\Application\Engagement\Services;
use Illuminate\Pagination\LengthAwarePaginator;

interface EntityEnhancementServiceInterface
{
    public function enhanceWithStats(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    public function enhanceWithHashtags(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    public function enhanceWithOptions(LengthAwarePaginator $entities,string $entityType, bool $includeStats = false, bool $includeHashtags = false): LengthAwarePaginator;
}
