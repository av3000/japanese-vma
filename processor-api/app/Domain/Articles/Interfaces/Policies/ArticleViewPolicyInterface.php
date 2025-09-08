<?php
namespace App\Domain\Articles\Interfaces\Policies;

use App\Infrastructure\Persistence\Models\Article;
use App\Http\User;

interface ArticleViewPolicyInterface
{
    /**
     * Get domain-based visibility criteria for a user
     * Returns business rules as data, not database operations
     */
    public function getVisibilityCriteria(?User $user): array;

    /**
     * Check if user can view specific article
     * Works with domain objects, not database queries
     */
    public function canView(?User $user, Article $article): bool;
}
