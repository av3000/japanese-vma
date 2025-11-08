<?php
namespace App\Domain\Articles\Factories;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId, JlptLevels};
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};
use App\Domain\Shared\Enums\ObjectTemplateType;

class ArticleFactory
{
    public static function createFromDTO(ArticleCreateDTO $dto, UserId $authorId, UserName $authorName): Article
    {
        return new Article(
            id: null,
            uuid: EntityId::generate(),
            entityTypeUid: new EntityId(ObjectTemplateType::ARTICLE->value),
            authorId: $authorId,
            authorName: $authorName,
            titleJp: new ArticleTitle($dto->title_jp),
            titleEn: $dto->title_en ? new ArticleTitle($dto->title_en) : null,
            contentJp: new ArticleContent($dto->content_jp),
            contentEn: $dto->content_en ? new ArticleContent($dto->content_en) : null,
            sourceUrl: new ArticleSourceUrl($dto->source_link),
            publicity: $dto->publicity ? PublicityStatus::PUBLIC : PublicityStatus::PRIVATE,
            status: ArticleStatus::PENDING,
            jlptLevels: JlptLevels::empty(),
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
