<?php

namespace App\Domains\Articles\ManageArticles\Web\ViewHelpers;

use App\Domain\Articles\Models\Article;
use App\Models\User;

class ArticleViewHelper
{
    /**
     * Format article data for API responses
     * Following Monica's DTO pattern
     */
    public static function dto(Article $article, User $user): array
    {
        return [
            'id' => $article->id,
            'title_jp' => $article->title_jp,
            'title_en' => $article->title_en,
            'content_preview' => mb_substr($article->content_jp, 0, 100) . '...', // Smart preview
            'is_published' => $article->isPublished(),
            'can_be_published' => $article->canBePublished(),
            'created_at' => $article->created_at->toISOString(),
            'updated_at' => $article->updated_at->toISOString(),

            // Author information
            'author' => [
                'id' => $article->user->id,
                'name' => $article->user->name,
            ],

            // User permissions for this article
            'permissions' => [
                'can_edit' => $user->can('update', $article),
                'can_delete' => $user->can('delete', $article),
                'can_publish' => $user->can('publish', $article),
            ],

            // Action URLs following Monica's pattern
            'urls' => [
                'show' => route('api.articles.show', $article->id),
                'edit' => route('api.articles.edit', $article->id),
                'delete' => route('api.articles.destroy', $article->id),
            ],
        ];
    }

    /**
     * Format article data for listing views (lighter payload)
     */
    public static function summary(Article $article, User $user): array
    {
        return [
            'id' => $article->id,
            'title_jp' => $article->title_jp,
            'is_published' => $article->isPublished(),
            'created_at' => $article->created_at->toISOString(),
            'author_name' => $article->user->name,
        ];
    }
}
