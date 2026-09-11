<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsInterface;
use App\Domain\Articles\DTOs\ArticlePdfExportData;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
// use App\Infrastructure\Persistence\Builders\KanjiRelationQueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly ArticleMapper $articleMapper,
        private readonly WordMapper $wordMapper,
        // private readonly KanjiRelationQueryBuilder $kanjiRelationQueryBuilder
    ) {
    }

    /**
     * Create a new article in persistence.
     *
     * @param DomainArticle $article The domain article to create
     *
     * @throws \Illuminate\Database\QueryException On database constraint violation
     *
     * @return DomainArticle The created article with generated ID and relationships
     */
    public function create(DomainArticle $article): DomainArticle
    {
        // TODO: use class::method if needed ArticleMapper::mapToEntity($article);
        $mappedArticle = $this->articleMapper->mapToEntity($article);
        $entityArticle = PersistenceArticle::create($mappedArticle);
        $entityArticle->load('user');

        return $this->articleMapper->mapToCreatedArticleDomain($entityArticle);
    }

    /**
     * Update an existing article in persistence.
     *
     * @param DomainArticle $article The domain article with updated state
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article doesn't exist
     */
    public function update(DomainArticle $article): void
    {
        $entityArticle = PersistenceArticle::with('user')
            ->where('uuid', $article->getUid()->value())
            ->firstOrFail();

        $this->articleMapper->mapToExistingEntity($article, $entityArticle);
        $entityArticle->save();

        // TODO: update attached kanjis
    }

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
    public function deleteById(int $id): bool
    {
        $persistenceArticle = PersistenceArticle::findOrFail($id);

        $persistenceArticle->kanjis()->detach();
        $persistenceArticle->words()->detach();

        return $persistenceArticle->delete();
    }

    /**
     * Find articles by author user ID with limit.
     *
     * Returns most recent articles by a specific user, ordered by creation date descending.
     * Eager loads user and kanjis relationships to avoid N+1 queries.
     *
     * @param UserId $userId The author's user ID
     * @param int $limit Maximum number of articles to return (default: 10)
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return array<array> Array of article arrays (raw Eloquent toArray() output, not domain models)
     *
     * @todo This returns raw arrays instead of domain models - inconsistent with other methods
     */
    public function findByUserId(UserId $userId, int $limit = 10): array
    {
        return PersistenceArticle::where('user_id', $userId->value())
            ->with(['user', 'kanjis'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get integer ID from article UUID.
     *
     * Performs a lightweight query returning only the ID column.
     * Useful when you need the integer ID for operations but only have the public UUID.
     *
     * @param EntityId $entityUuid The article's public UUID
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return int|null The article's integer ID, or null if UUID not found
     */
    public function getIdByUuid(EntityId $entityUuid): ?int
    {
        return PersistenceArticle::where('uuid', $entityUuid->value())->value('id');
    }

    /**
     * Find article by public UUID with optional selective eager loading.
     *
     *
     * @param EntityId $articleUuid The article's public UUID
     *
     * @throws \Illuminate\Database\QueryException On database failure
     *
     * @return DomainArticle|null The domain article if found, null if not found
     */
    public function findByPublicUid(EntityId $articleUuid, ?ArticleIncludeOptionsInterface $options = null): ?DomainArticle
    {
        $query = PersistenceArticle::query()
            ->with(['user'])
            ->where('uuid', $articleUuid->value());

        if ($options?->includeKanjis()) {
            $query->with(['kanjis']);
        }

        if ($options?->includeWords()) {
            $query->with(['words']);
        }

        $persistenceArticle = $query->first();

        return $persistenceArticle
            ? $this->articleMapper->mapToDomain($persistenceArticle, $options)
            : null;
    }

    public function findPdfExportData(EntityId $articleUuid, bool $includeKanjis, bool $includeWords): ?ArticlePdfExportData
    {
        $query = PersistenceArticle::query()
            ->with(['user'])
            ->where('uuid', $articleUuid->value());

        if ($includeKanjis) {
            $query->with('kanjis');
        }

        if ($includeWords) {
            $query->with('words');
        }

        $persistenceArticle = $query->first();

        if ($persistenceArticle === null) {
            return null;
        }

        return new ArticlePdfExportData(
            article: $this->articleMapper->mapToDomain($persistenceArticle),
            kanjis: $includeKanjis ? $this->mapKanjisForPdf($persistenceArticle) : [],
            words: $includeWords ? $this->mapWordsForPdf($persistenceArticle) : [],
        );
    }

    public function findWordPaginatorByArticleId(int $articleId, Pagination $pagination): ?LengthAwarePaginator
    {
        $article = PersistenceArticle::find($articleId);

        if ($article === null) {
            return null;
        }

        $paginator = $article->words()->paginate(
            perPage: $pagination->per_page,
            page: $pagination->page
        );

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($persistenceWord) => $this->wordMapper->mapToDomain($persistenceWord)
            )
        );

        return $paginator;
    }

    public function findModerationQueue(Pagination $pagination): Articles
    {
        $paginator = PersistenceArticle::query()
            ->with('user')
            ->whereIn('status', [ArticleStatus::PENDING->value, ArticleStatus::REVIEWING->value])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($pagination->per_page, ['*'], 'page', $pagination->page);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (PersistenceArticle $article) => $this->articleMapper->mapToDomain($article),
            ),
        );

        return Articles::fromEloquentPaginator($paginator);
    }

    public function updateStatus(EntityId $articleUuid, ArticleStatus $status): ?ArticleStatus
    {
        $article = PersistenceArticle::where('uuid', $articleUuid->value())->first();

        if ($article === null) {
            return null;
        }

        $article->status = $status;
        $article->save();

        return $article->status;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapKanjisForPdf(PersistenceArticle $article): array
    {
        if (! $article->relationLoaded('kanjis')) {
            return [];
        }

        return $article->kanjis
            ->map(fn ($kanji): array => [
                'id' => $kanji->id,
                'kanji' => $kanji->kanji,
                'onyomi' => $this->splitKanjiPdfValues($kanji->onyomi),
                'kunyomi' => $this->splitKanjiPdfValues($kanji->kunyomi),
                'meaning' => $this->splitKanjiPdfValues($kanji->meaning),
                'jlpt' => $kanji->jlpt,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitKanjiPdfValues(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode('|', $value)),
            static fn (string $part): bool => $part !== ''
        ));
    }

    /**
     * @return array<int, \App\Domain\JapaneseMaterial\Words\Models\Word>
     */
    private function mapWordsForPdf(PersistenceArticle $article): array
    {
        if (! $article->relationLoaded('words')) {
            return [];
        }

        return $article->words
            ->map(fn ($word) => $this->wordMapper->mapToDomain($word))
            ->all();
    }

    /**
     * Syncs a list of Kanji IDs to an article.
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $kanjiIds An array of Kanji internal IDs to attach.
     */
    public function syncKanjis(int $articleId, array $kanjiIds): void
    {
        $existingKanjiIds = DB::table('article_kanji')
            ->where('article_id', $articleId)
            ->pluck('kanji_id')
            ->toArray();

        $kanjiIdsToAdd = array_diff($kanjiIds, $existingKanjiIds);
        $kanjiIdsToRemove = array_diff($existingKanjiIds, $kanjiIds);

        DB::transaction(function () use ($articleId, $kanjiIdsToAdd, $kanjiIdsToRemove) {
            if (! empty($kanjiIdsToRemove)) {
                DB::table('article_kanji')
                    ->where('article_id', $articleId)
                    ->whereIn('kanji_id', $kanjiIdsToRemove)
                    ->delete();
            }

            if (! empty($kanjiIdsToAdd)) {
                $pivotRecords = array_map(fn ($kanjiId) => [
                    'article_id' => $articleId,
                    'kanji_id' => $kanjiId,
                ], $kanjiIdsToAdd);

                foreach (array_chunk($pivotRecords, 1000) as $chunk) {
                    DB::table('article_kanji')->insert($chunk);
                }
            }
        });
    }

    /**
     * Syncs a list of Word IDs to an article.
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $wordIds An array of Word internal IDs to attach.
     */
    public function syncWords(int $articleId, array $wordIds): void
    {
        $existingWordIds = DB::table('article_word')
            ->where('article_id', $articleId)
            ->pluck('word_id')
            ->toArray();

        $wordIdsToAdd = array_diff($wordIds, $existingWordIds);
        $wordIdsToRemove = array_diff($existingWordIds, $wordIds);

        DB::transaction(function () use ($articleId, $wordIdsToAdd, $wordIdsToRemove): void {
            if (! empty($wordIdsToRemove)) {
                DB::table('article_word')
                    ->where('article_id', $articleId)
                    ->whereIn('word_id', $wordIdsToRemove)
                    ->delete();
            }

            if (! empty($wordIdsToAdd)) {
                $pivotRecords = array_map(static fn (int $wordId): array => [
                    'article_id' => $articleId,
                    'word_id' => $wordId,
                ], $wordIdsToAdd);

                foreach (array_chunk($pivotRecords, 1000) as $chunk) {
                    DB::table('article_word')->insert($chunk);
                }
            }
        });
    }
}
