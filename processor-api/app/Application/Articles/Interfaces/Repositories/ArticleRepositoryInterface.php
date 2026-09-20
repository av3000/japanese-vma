<?php

namespace App\Application\Articles\Interfaces\Repositories;

use App\Domain\Articles\DTOs\ArticleIncludeOptionsInterface;
use App\Domain\Articles\DTOs\ArticleKanjiListResultDTO;
use App\Domain\Articles\DTOs\ArticlePdfExportData;
use App\Domain\Articles\DTOs\ArticleProcessingSourceDTO;
use App\Domain\Articles\DTOs\ArticleWordListResultDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\JlptLevels;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;

interface ArticleRepositoryInterface
{
    /**
     * Create a new article in persistence.
     *
     * @param DomainArticle $article The domain article to create
     *
     * @throws \Illuminate\Database\QueryException On database constraint violation
     *
     * @return DomainArticle The created article with generated ID
     */
    public function create(DomainArticle $article): DomainArticle;

    /**
     * Update an existing article in persistence.
     *
     * @param DomainArticle $article The domain article with updated state
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article doesn't exist
     */
    public function update(DomainArticle $article): void;

    /**
     * Find article by public UUID with optional selective eager loading.
     *
     *
     * @param EntityId $articleUuid The article's public UUID
     * @param ArticleIncludeOptionsInterface|null $dto Options for eager loading:
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return DomainArticle|null The domain article if found, null if not found
     */
    public function findByPublicUid(EntityId $articleUuid, ?ArticleIncludeOptionsInterface $dto = null): ?DomainArticle;

    public function findPdfExportData(EntityId $articleUuid, bool $includeKanjis, bool $includeWords): ?ArticlePdfExportData;

    /**
     * One page of the words attached to an article, or null when the article does not exist.
     */
    public function findWordPage(EntityId $articleUuid, Pagination $pagination): ?ArticleWordListResultDTO;

    /**
     * One page of the kanji attached to an article, or null when the article does not exist.
     */
    public function findKanjiPage(EntityId $articleUuid, Pagination $pagination): ?ArticleKanjiListResultDTO;

    public function findModerationQueue(Pagination $pagination): Articles;

    public function updateStatus(EntityId $articleUuid, ArticleStatus $status): ?ArticleStatus;

    /**
     * Delete article by integer ID with proper relationship cleanup.
     * Note: Engagement data (likes, views, comments) should be cleaned up
     * by the service layer before calling this method.
     *
     * @param int $id The article's integer ID (not UUID)
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article with ID not found
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return bool True if deleted successfully
     */
    public function deleteById(int $id): bool;

    /**
     * Find articles by author user ID with limit.
     *
     * Returns most recent articles by a specific user, ordered by creation date.
     * Eager loads user and kanjis relationships.
     *
     * @param UserId $authorId The author's user ID
     * @param int $limit Maximum number of articles to return (default: 10)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<array> Array of article arrays (not domain models, raw Eloquent arrays)
     */
    public function findByUserId(UserId $authorId, int $limit = 10): array;

    /**
     * Get integer ID from article UUID.
     *
     * Useful for operations that require the integer ID but only have the public UUID.
     *
     * @param EntityId $entityUuid The article's public UUID
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return int|null The article's integer ID, or null if not found
     */
    public function getIdByUuid(EntityId $entityUuid): ?int;

    /**
     * Syncs a list of Kanji IDs to an article.
     * This replaces any existing kanjis attached to the article. When JLPT counters are given
     * they are written on the article row in the same transaction as the pivot sync, so the
     * two can never disagree (#250).
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $kanjiIds An array of Kanji internal IDs to attach.
     */
    public function syncKanjis(int $articleId, array $kanjiIds, ?JlptLevels $jlptLevels = null): void;

    /**
     * Syncs a list of Word IDs to an article.
     * This replaces any existing words attached to the article.
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $wordIds An array of Word internal IDs to attach.
     */
    public function syncWords(int $articleId, array $wordIds): void;

    /**
     * The fields the content-processing job runs over, plus the version it must match.
     */
    public function findProcessingSource(EntityId $articleUuid): ?ArticleProcessingSourceDTO;

    /**
     * Increment `content_version` and return the new value. Call inside the write transaction
     * that changed `title_jp` or `content_jp`.
     */
    public function bumpContentVersion(int $articleId): int;

    /**
     * Replace kanji attachments, word attachments and JLPT counters atomically. Empty id lists
     * clear the corresponding attachments (#257).
     *
     * @param int[] $kanjiIds
     * @param int[] $wordIds
     */
    public function syncContentProcessing(int $articleId, array $kanjiIds, array $wordIds, JlptLevels $jlptLevels): void;
}
