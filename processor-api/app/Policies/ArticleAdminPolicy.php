<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;

/**
 * Panel access to articles. Authoring stays in the React client, so there is no create. Status,
 * publicity and delete additionally pass the application ArticlePolicy at the action.
 */
class ArticleAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('articles.view');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->hasPanelPermission('articles.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->hasPanelPermission('articles.update');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasPanelPermission('articles.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
