<?php

namespace App\Application\Engagement\Services;
// use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Articles\Models\{Articles, Article};

interface EngagementServiceInterface
{
    // public function enhanceWithStats(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    public function enhanceArticlesWithStats(Articles $articles): Articles;
    public function enhanceArticleWithStats(Article $article): Article;
    public function enhanceWithComments($article): void;
    // public function enhanceWithHashtags(LengthAwarePaginator $entities, string $entityType): LengthAwarePaginator;
    // public function enhanceWithOptions(LengthAwarePaginator $entities,string $entityType, bool $include_stats = false, bool $include_hashtags = false): LengthAwarePaginator;
}
