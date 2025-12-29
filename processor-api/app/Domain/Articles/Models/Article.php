<?php

namespace App\Domain\Articles\Models;

use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId, JlptLevels};
use App\Domain\JapaneseMaterial\Kanji\Models\Kanji as DomainKanji;

class Article
{
    /**
     * @param ?int $id
     * @param EntityId $uuid
     * @param EntityId|string $entityTypeUid
     * @param UserId $authorId
     * @param UserName $authorName
     * @param ArticleTitle $titleJp
     * @param ?ArticleTitle $titleEn
     * @param ArticleContent $contentJp
     * @param ?ArticleContent $contentEn
     * @param ArticleSourceUrl $sourceUrl
     * @param PublicityStatus $publicity
     * @param ArticleStatus $status
     * @param JlptLevels $jlptLevels
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     * @param DomainKanji[] $kanjis
     * */
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private EntityId|string $entityTypeUid,
        private UserId $authorId,
        private UserName $authorName,
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

    ) {}

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
     * @return DomainKanji[]
     */
    public function getKanjis(): array
    {
        return $this->kanjis;
    }
}
