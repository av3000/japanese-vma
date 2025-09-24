<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Domain\Articles\Models\Article as DomainArticle;

use App\Domain\Shared\ValueObjects\{EntityId, UserId, JlptLevels};
use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl, ArticleTags};
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};

class ArticleMapper
{
    public static function mapToDomain(PersistenceArticle $entity): DomainArticle
    {
        return new DomainArticle(
            new EntityId($entity->unique_id),
            new UserId($entity->user_id),
            new UserName($entity->user->name),
            new ArticleTitle($entity->title_jp),
            $entity->title_en ? new ArticleTitle($entity->title_en) : null,
            new ArticleContent($entity->content_jp),
            $entity->content_en ? new ArticleContent($entity->content_en) : null,
            new ArticleSourceUrl($entity->source_link),
            $entity->publicity,
            $entity->status,
            new JlptLevels(
                (int)$entity->n1,
                (int)$entity->n2,
                (int)$entity->n3,
                (int)$entity->n4,
                (int)$entity->n5,
                (int)$entity->uncommon
            ),
            ArticleTags::fromHashtagsCollection($entity->hashtags ?? collect()),
            // TODO: create ArticleTags proper domain object
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable(),
             $entity->likesTotal ?? null,
            $entity->downloadsTotal ?? null,
            $entity->viewsTotal ?? null,
            $entity->commentsTotal ?? null,
        );
    }

    public static function mapToEntity(DomainArticle $article): array
    {
        return [
            'unique_id' => $article->getUid()->value(),
            'user_id' => $article->getAuthorId()->value(),
            'title_jp' => $article->getTitleJp()->value(),
            'title_en' => $article->getTitleEn()?->value(),
            'content_jp' => $article->getContentJp()->value(),
            'content_en' => $article->getContentEn()?->value(),
            'source_link' => $article->getSourceUrl()->value(),
            'publicity' => $article->getPublicity()->value,
            'status' => $article->getStatus()->value,
            'n1' => (string)$article->getJlptLevels()->n1(),
            'n2' => (string)$article->getJlptLevels()->n2(),
            'n3' => (string)$article->getJlptLevels()->n3(),
            'n4' => (string)$article->getJlptLevels()->n4(),
            'n5' => (string)$article->getJlptLevels()->n5(),
            'uncommon' => (string)$article->getJlptLevels()->uncommon(),
            'created_at' => $article->getCreatedAt(),
            'updated_at' => $article->getUpdatedAt(),
        ];
    }
}
