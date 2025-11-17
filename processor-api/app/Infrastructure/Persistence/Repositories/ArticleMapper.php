<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Domain\Articles\Models\Article as DomainArticle;

use App\Domain\Shared\ValueObjects\{EntityId, UserId, UserName, JlptLevels};
use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};

class ArticleMapper
{
    public static function mapToDomain(PersistenceArticle $entity): DomainArticle
    {
        return new DomainArticle(
            $entity->id,
            new EntityId($entity->uuid),
            $entity->entity_type_uuid ? new EntityId($entity->entity_type_uuid) : 'broken',
            new UserId($entity->user_id),
            new UserName($entity->user?->name ?? 'Unknown User'),
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
            // TODO: create ArticleTags proper domain object
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable(),
        );
    }

    // TODO: shouldnt retun type be persistence article?
    public static function mapToEntity(DomainArticle $article): array
    {
        return [
            'uuid' => $article->getUid()->value(),
            'user_id' => $article->getAuthorId()->value(),
            'entity_type_uuid' => $article->getEntityTypeUid()->value(),
            'title_jp' => $article->getTitleJp()->value,
            'title_en' => $article->getTitleEn()?->value,
            'content_jp' => $article->getContentJp()->value,
            'content_en' => $article->getContentEn()?->value,
            'source_link' => $article->getSourceUrl()->value,
            'publicity' => $article->getPublicity()->value,
            'status' => $article->getStatus()->value,
            'n1' => (string)$article->getJlptLevels()->n1,
            'n2' => (string)$article->getJlptLevels()->n2,
            'n3' => (string)$article->getJlptLevels()->n3,
            'n4' => (string)$article->getJlptLevels()->n4,
            'n5' => (string)$article->getJlptLevels()->n5,
            'uncommon' => (string)$article->getJlptLevels()->uncommon,
            'created_at' => $article->getCreatedAt(),
            'updated_at' => $article->getUpdatedAt(),
        ];
    }

     /**
     * Map domain model onto EXISTING persistence entity (Domain → Entity).
     * Mutates the entity with updated values. Used for updates.
     *
     * @param DomainArticle $article Domain article with updated state
     * @param PersistenceArticle $entity Existing tracked entity to update
     * @return void
     */
    public static function mapToExistingEntity(DomainArticle $article, PersistenceArticle $entity): void
    {
        $entity->title_jp = $article->getTitleJp()->value;
        $entity->title_en = $article->getTitleEn()?->value;
        $entity->content_jp = $article->getContentJp()->value;
        $entity->content_en = $article->getContentEn()?->value;
        $entity->source_link = $article->getSourceUrl()->value;
        $entity->publicity = $article->getPublicity()->value;
        $entity->status = $article->getStatus()->value;

        $jlptLevels = $article->getJlptLevels();
        $entity->n1 = (string)$jlptLevels->n1;
        $entity->n2 = (string)$jlptLevels->n2;
        $entity->n3 = (string)$jlptLevels->n3;
        $entity->n4 = (string)$jlptLevels->n4;
        $entity->n5 = (string)$jlptLevels->n5;
        $entity->uncommon = (string)$jlptLevels->uncommon;
    }
}
