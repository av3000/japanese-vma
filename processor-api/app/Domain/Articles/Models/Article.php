<?php

namespace App\Domain\Articles\Models;

use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\JapaneseMaterial\Kanji\Models\Kanji as DomainKanji;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\JlptLevels;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;

class Article
{
    /**
     * @param  ?int  $id
     * @param  ?ArticleTitle  $titleEn
     * @param  ?ArticleContent  $contentEn
     * @param  array<int, DomainKanji>  $kanjis
     * */
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private EntityId|string $entityTypeUid,
        private UserId $authorId,
        private UserName $authorName,
        private EntityId $authorUuid,
        private ArticleTitle $titleJp,
        private ?ArticleTitle $titleEn,
        private ArticleContent $contentJp,
        private ?ArticleContent $contentEn,
        private ArticleSourceUrl $sourceUrl,
        private PublicityStatus $publicity,
        private ArticleStatus $status,
        private JlptLevels $jlptLevels,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private array $kanjis = [],
    ) {
    }

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUid(): EntityId
    {
        return $this->uuid;
    }

    public function getEntityTypeUid(): EntityId|string
    {
        return $this->entityTypeUid;
    }

    public function getAuthorId(): UserId
    {
        return $this->authorId;
    }

    public function getAuthorName(): UserName
    {
        return $this->authorName;
    }

    public function getAuthorUuid(): EntityId
    {
        return $this->authorUuid;
    }

    public function getTitleJp(): ArticleTitle
    {
        return $this->titleJp;
    }

    public function getTitleEn(): ?ArticleTitle
    {
        return $this->titleEn;
    }

    public function getContentJp(): ArticleContent
    {
        return $this->contentJp;
    }

    public function getContentEn(): ?ArticleContent
    {
        return $this->contentEn;
    }

    public function getSourceUrl(): ArticleSourceUrl
    {
        return $this->sourceUrl;
    }

    public function getPublicity(): PublicityStatus
    {
        return $this->publicity;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function getJlptLevels(): JlptLevels
    {
        return $this->jlptLevels;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array<int, DomainKanji>
     */
    public function getKanjis(): array
    {
        return $this->kanjis;
    }
}
