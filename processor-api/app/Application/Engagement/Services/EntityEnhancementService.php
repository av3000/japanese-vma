<?php
namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityHashtagsAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;

use Illuminate\Pagination\LengthAwarePaginator;

class EntityEnhancementService implements EntityEnhancementServiceInterface
{
    public function __construct(
        private LoadEntityHashtagsAction $loadHashtags,
        private LoadEntityStatsAction $loadStats
    ) {}

    /**
     * Enhance any entity list with statistical data
     *
     * This generic method works with articles, lists, posts, or any other
     * entity that uses your template-based engagement system.
     */
    public function enhanceWithStats(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator
    {
        $this->loadStats->execute($entities, $entityType);
        return $entities;
    }

    /**
     * Enhance any entity list with hashtag data
     */
    public function enhanceWithHashtags(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator
    {
        $this->loadHashtags->execute($entities, $entityType);
        return $entities;
    }

    /**
     * Apply multiple enhancements based on configuration
     *
     * This method provides a convenient way to apply multiple enhancements
     * based on what's requested, reducing the number of method calls needed.
     */
    public function enhanceWithOptions(
        LengthAwarePaginator $entities,
        string $entityType,
        bool $includeStats = false,
        bool $includeHashtags = false
    ): LengthAwarePaginator {
        if ($includeHashtags) {
            $this->enhanceWithHashtags($entities, $entityType);
        }

        if ($includeStats) {
            $this->enhanceWithStats($entities, $entityType);
        }

        return $entities;
    }
}
