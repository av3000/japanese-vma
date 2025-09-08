<?php
namespace App\Domain\Articles\Models;

use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\ValueObjects\{ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Articles\ValueObjects\JlptLevels;
use App\Domain\Articles\ValueObjects\ExtractedKanjis;
use App\Domain\Articles\ValueObjects\ArticleTags;
use App\Domain\Shared\Enums\{PublicityStatus, ArticleStatus};
use App\Domain\Shared\ValueObjects\UserId;

class Article
{
    private ArticleId $id;
    private UserId $authorId;
    private ArticleTitle $titleJp;
    private ?ArticleTitle $titleEn;
    private ArticleContent $contentJp;
    private ?ArticleContent $contentEn;
    private ArticleSourceUrl $sourceUrl;
    private PublicityStatus $publicity;
    private ArticleStatus $status;
    private ExtractedKanjis $kanjis;
    private JlptLevels $jlptLevels;
    private ArticleTags $tags;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    // Private constructor - forces creation through factory methods
    private function __construct(
        ArticleId $id,
        UserId $authorId,
        ArticleTitle $titleJp,
        ?ArticleTitle $titleEn,
        ArticleContent $contentJp,
        ?ArticleContent $contentEn,
        ArticleSourceUrl $sourceUrl,
        PublicityStatus $publicity,
        ArticleTags $tags
    ) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->titleJp = $titleJp;
        $this->titleEn = $titleEn;
        $this->contentJp = $contentJp;
        $this->contentEn = $contentEn;
        $this->sourceUrl = $sourceUrl;
        $this->publicity = $publicity;
        $this->tags = $tags;

        // Business rule: All new articles start as pending
        $this->status = ArticleStatus::PENDING;

        // Core business logic: Extract kanjis and calculate levels automatically
        $this->processJapaneseContent();

        // Business rule: Articles with high difficulty need review before publication
        $this->applyPublicationRules();

        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
