<?php

namespace App\Domain\Articles\Interfaces;

use App\Domain\Articles\Http\Models\Article;
use App\Http\User;
use Illuminate\Database\Eloquent\Builder;

interface ArticleViewPolicyInterface
{
    /**
     * Apply visibility filters to query based on user permissions
     *
     * @param Builder $query
     * @param User|null $user
     * @return Builder
     */
    public function applyVisibilityFilter(Builder $query, ?User $user): Builder;

    /**
     * Check if user can view specific article
     *
     * @param User|null $user
     * @param Article $article
     * @return bool
     */
    public function canView(?User $user, Article $article): bool;
}
