<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use App\Domain\Articles\DTOs\{ArticleListDTO, ArticleIncludeOptionsDTO, ArticleCriteriaDTO};
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\ValueObjects\{ArticleId, ArticleSortCriteria};
use App\Domain\Shared\ValueObjects\{UserId, EntityId};
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct() {}

    public function save(DomainArticle $article): ?DomainArticle
    {
        return DB::transaction(function () use ($article) {
            $mappedArticle = ArticleMapper::mapToEntity($article);
            $entityArticle = PersistenceArticle::create($mappedArticle);
            $entityArticle->load('user');

            return ArticleMapper::mapToDomain($entityArticle);
        });
    }

    // public function saveWithKanjis(DomainArticle $article, array $kanjiIds): DomainArticle
    // {
    //     return DB::transaction(function () use ($article, $kanjiIds) {
    //         $persistenceData = ArticleMapper::mapToEntity($article);

    //         $persistenceArticle = PersistenceArticle::updateOrCreate(
    //             ['uuid' => $persistenceData['uuid']],
    //             $persistenceData
    //         );

    //         if (!empty($kanjiIds)) {
    //             $persistenceArticle->kanjis()->sync($kanjiIds);
    //         }

    //         return ArticleMapper::mapToDomain(
    //             $persistenceArticle->fresh(['user', 'kanjis', 'hashtags'])
    //         );
    //     });
    // }

    public function findByPublicUid(EntityId $uid, ArticleIncludeOptionsDTO $dto): ?DomainArticle
    {
        // TODO: fetch by UUID instead of primary ID
        $query = PersistenceArticle::query();

        $with = [];
        if ($dto->include_user) $with[] = 'user';
        if ($dto->include_kanjis) $with[] = 'kanjis';
        if ($dto->include_words) $with[] = 'words';

        $persistenceArticle = $query->with($with)
            ->where('uuid', $uid->value())
            ->first();

         return $persistenceArticle ? ArticleMapper::mapToDomain($persistenceArticle) : null;
        // TODO: use mapper to convert persistence model to domain model

        // return ArticleMapper::mapToDomain($entityArticle);
    }

    public function deleteById(ArticleId $id): bool
    {
        return DB::transaction(function () use ($id) {
            $article = Article::find($id);

            if (!$article) {
                return false;
            }

            $article->kanjis()->detach();
            $article->words()->detach();

            // Clean up related data (could move to separate repositories)
            $this->cleanupRelatedData($article);

            return $article->delete();
        });
    }

    public function findByUserId(UserId $userId, int $limit = 10): array
    {
        return Article::where('user_id', $userId)
            ->with(['user', 'kanjis'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function attachHashtags(Article $article, string $tags, int $userId): void
    {
        $objectTemplateId = \App\Http\Models\ObjectTemplate::where('title', 'article')->first()->id;

        // Using existing global function - should be repository method
        attachHashTags($tags, $article, $objectTemplateId);
    }

    private function cleanupRelatedData(Article $article): void
    {
        $objectTemplateId = \App\Http\Models\ObjectTemplate::where('title', 'article')->first()->id;

        DB::table('likes')->where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)->delete();

        DB::table('views')->where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)->delete();

        DB::table('comments')->where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)->delete();

        DB::table('hashtags')->where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)->delete();
    }

    public function getIdByUuid(EntityId $entityUuid): int | null
    {
        return PersistenceArticle::where('uuid', $entityUuid)->value('id');
    }

    /**
     * Find articles with filters applied based on DTO and user permissions
     * This method encapsulates all query building logic while keeping the
     * service layer focused on business orchestration
     */
    public function findByCriteria(ArticleCriteriaDTO $criteria): Articles
    {
        $query = PersistenceArticle::query()->with(['user']);

        $this->applyVisibilityFilters($query, $criteria->visibilityRules);
        // dd($query, $criteria);
        $this->applyContentFilters($query, $criteria);
        $this->applySorting($query, $criteria->sort);

        $paginatedResults = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page
        );

        $domainArticles = $paginatedResults->getCollection()->map(function ($persistenceArticle) {
            return ArticleMapper::mapToDomain($persistenceArticle);
        });

        $paginatedResults->setCollection($domainArticles);

        return Articles::fromEloquentPaginator($paginatedResults);
    }

    /**
     * Apply permission-based filtering based on user role and article publicity
     * This method encapsulates the complex business rules around article visibility
     */
    private function applyVisibilityFilters($query, array $rules): void
    {
        if (empty($rules)) {
            return;
        }

        if ($rules['publicity'] === 'all') {
            return;
        }

        $query->where(function($q) use ($rules) {
            // TODO: rules should be defined as const or enums or some other form than raw string array.
            if(isset($rules['access_own_private']) && $rules['access_own_private']) {
                $q->where('publicity', PublicityStatus::PUBLIC)
                  ->orWhere(function($subQ) use ($rules) {
                      $subQ->where('publicity', PublicityStatus::PRIVATE)
                           ->where('user_id', $rules['user_id']);
                  });
            } else {
                $q->whereIn('publicity', $rules['publicity']);
            }
        });
    }

    /**
     * Apply content-based filters from the DTO
     * This method handles search terms and category filtering
     */
    private function applyContentFilters($query, ArticleCriteriaDTO $criteria): void
    {
        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->category);
        }

        if ($criteria->search !== null) {
            $searchValue = $criteria->search->value;
            $query->where(function($q) use ($searchValue) {
                $q->where('title_jp', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $searchValue . '%');
            });
        }
    }

    private function applySorting($query, ArticleSortCriteria $sort): void
    {
        $query->orderBy($sort->field->value, $sort->direction->value);
    }
}
