<?php
namespace App\Application\Articles\Services;

use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Shared\ValueObjects\EntityId;

interface ArticleKanjiProcessingServiceInterface
{
    public function processArticleKanjis(EntityId $articleUid): DomainArticle;
}
