<?php
namespace App\Domain\Articles\Models;

use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Articles\ValueObjects\JlptLevels;
use App\Domain\Articles\ValueObjects\ArticleTags;
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};
use App\Domain\Shared\ValueObjects\UserId;

class Article
{
     public function __construct(
        private EntityId $uid,
        private UserId $authorId,
        private ArticleTitle $titleJp,
        private ?ArticleTitle $titleEn,
        private ArticleContent $contentJp,
        private ?ArticleContent $contentEn,
        private ArticleSourceUrl $sourceUrl,
        private PublicityStatus $publicity,
        private ArticleStatus $status,
        private JlptLevels $jlptLevels,
        private ArticleTags $tags,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}

    public static function create(
        EntityId $uid,
        UserId $authorId,
        ArticleTitle $titleJp,
        ?ArticleTitle $titleEn,
        ArticleContent $contentJp,
        ?ArticleContent $contentEn,
        ArticleSourceUrl $sourceUrl,
        PublicityStatus $publicity,
        ArticleTags $tags
    ): self {
        return new self(
            $uid,
            $authorId,
            $titleJp,
            $titleEn,
            $contentJp,
            $contentEn,
            $sourceUrl,
            $publicity,
            ArticleStatus::PENDING,
            JlptLevels::empty(),
            $tags,
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }

    public function getUid(): EntityId { return $this->uid; }
    public function getAuthorId(): UserId { return $this->authorId; }
    public function getTitleJp(): ArticleTitle { return $this->titleJp; }
    public function getTitleEn(): ?ArticleTitle { return $this->titleEn; }
    public function getContentJp(): ArticleContent { return $this->contentJp; }
    public function getContentEn(): ?ArticleContent { return $this->contentEn; }
    public function getSourceUrl(): ArticleSourceUrl { return $this->sourceUrl; }
    public function getPublicity(): PublicityStatus { return $this->publicity; }
    public function getStatus(): ArticleStatus { return $this->status; }
    public function getJlptLevels(): JlptLevels { return $this->jlptLevels; }
    public function getTags(): ArticleTags { return $this->tags; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
