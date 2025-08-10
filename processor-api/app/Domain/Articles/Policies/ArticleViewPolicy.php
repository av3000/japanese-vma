<?php
namespace App\Domain\Articles\Policies;

use App\Domain\Articles\Models\Article;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\User;
use Illuminate\Database\Eloquent\Builder;

class ArticleViewPolicy
{
    public function applyVisibilityFilter(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->where('publicity', PublicityStatus::PUBLIC);
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function($q) use ($user) {
            $q->where('publicity', PublicityStatus::PUBLIC)
              ->orWhere(function($subQ) use ($user) {
                  $subQ->where('publicity', PublicityStatus::PRIVATE)
                       ->where('user_id', $user->id);
              });
        });
    }

    public function canView(?User $user, Article $article): bool
    {
        if ($article->publicity === PublicityStatus::PUBLIC) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->id === $article->user_id;
    }
}
