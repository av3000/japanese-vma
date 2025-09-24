<?php
namespace App\Domain\Articles\Models;

use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Articles\ValueObjects\JlptLevels;
use App\Domain\Articles\ValueObjects\ArticleTags;
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId};

class Article
{
     public function __construct(
        private EntityId $uid,
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
        private ArticleTags $tags,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        // Optionals
        private ?int $likesCount = null,
        private ?int $downloadsCount = null,
        private ?int $viewsCount = null,
        private ?int $commentsCount = null,
    ) {}

    public static function create(
        EntityId $uid,
        UserId $authorId,
        UserName $authorName,
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
            $authorName,
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

    public function withStats(int $likes, int $downloads, int $views, int $comments): self
    {
        return new self(
            $this->uid,
            $this->authorId,
            $this->authorName,
            $this->titleJp,
            $this->titleEn,
            $this->contentJp,
            $this->contentEn,
            $this->sourceUrl,
            $this->publicity,
            $this->status,
            $this->jlptLevels,
            $this->tags,
            $this->createdAt,
            $this->updatedAt,
            $likes,
            $downloads,
            $views,
            $comments,
        );
    }

    public function getUid(): EntityId { return $this->uid; }
    public function getAuthorId(): UserId { return $this->authorId; }
    public function getAuthorName(): UserId { return $this->authorName; }
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

    public function getLikesCount(): ?int { return $this->likesCount; }
    public function getDownloadsCount(): ?int { return $this->downloadsCount; }
    public function getViewsCount(): ?int { return $this->viewsCount; }
    public function getCommentsCount(): ?int { return $this->commentsCount; }
}
