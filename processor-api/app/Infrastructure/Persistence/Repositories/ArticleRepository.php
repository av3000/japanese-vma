<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleViewPolicy;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\ValueObjects\ArticleId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private ArticleViewPolicy $viewPolicy
    ) {}

    public function save(DomainArticle $article): DomainArticle
    {
        return DB::transaction(function () use ($article, $tags) {
            $mappedArticle = ArticleMapper::mapToEntity($article);

            $entityArticle = PersistenceArticle::updateOrCreate(
                ['unique_id' => $mappedArticle['unique_id']],
                $mappedArticle
            );

            // Questionable implementation, not sure what is the exact outcome
            if (!$entityArticle->kanjis()->isEmpty()) {
                $entityArticle->kanjis()->sync($article['kanjis']);
            }

            // Should be handled with a separate service/repository and probably in the service layer.
            // wonder how tags look like before attaching
            if ($tags) {
                $this->attachHashtags($entityArticle, $article['tags'], $article['user_id']);
            }

            return ArticleMapper::mapToDomain(
                $entityArticle->fresh(['user', 'kanjis', 'hashtags'])
            );
        });
    }

    public function saveWithKanjis(DomainArticle $article, array $kanjiIds): DomainArticle
    {
        return DB::transaction(function () use ($article, $kanjiIds) {
            $persistenceData = ArticleMapper::mapToEntity($article);

            $persistenceArticle = PersistenceArticle::updateOrCreate(
                ['unique_id' => $persistenceData['unique_id']],
                $persistenceData
            );

            if (!empty($kanjiIds)) {
                $persistenceArticle->kanjis()->sync($kanjiIds);
            }

            return ArticleMapper::mapToDomain(
                $persistenceArticle->fresh(['user', 'kanjis', 'hashtags'])
            );
        });
    }

    public function findById(ArticleId $id): DomainArticle
    {
        $entityArticle = PersistenceArticle::with(['user', 'kanjis', 'words'])->find($id);
        // TODO: use mapper to convert persistence model to domain model
        if (!$entityArticle) {
            return null;
        }

        return ArticleMapper::mapToDomain($entityArticle);
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

    /**
     * Find articles with filters applied based on DTO and user permissions
     * This method encapsulates all query building logic while keeping the
     * service layer focused on business orchestration
     */
    public function findWithFilters(ArticleListDTO $dto, ?User $user = null): Articles
    {
        $query = PersistenceArticle::query()->with(['user']);
        $query = $this->applyPermissionFilters($query, $user);
        $query = $this->applyContentFilters($query, $dto);
        $query = $this->applySorting($query, $dto);

        $paginatedResults = $query->paginate(
            $dto->per_page ?? 10,
            ['*'],
            'page',
            request()->get('page', 1)
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
    private function applyPermissionFilters($query, ?User $user)
    {
        if (!$user) {
            // Anonymous users see only public articles
            return $query->where('publicity', PublicityStatus::PUBLIC);
        }

        if ($user->hasRole('admin')) {
            // Administrators can see all articles
            return $query;
        }

        // Regular authenticated users see public articles plus their own private articles
        return $query->where(function($q) use ($user) {
            $q->where('publicity', PublicityStatus::PUBLIC)
              ->orWhere(function($subQ) use ($user) {
                  $subQ->where('publicity', PublicityStatus::PRIVATE)
                       ->where('user_id', $user->id);
              });
        });
    }

    /**
     * Apply content-based filters from the DTO
     * This method handles search terms and category filtering
     */
    private function applyContentFilters($query, ArticleListDTO $dto)
    {
        // Apply category filter if specified
        if ($dto->category !== null) {
            $query->where('category_id', $dto->category);
        }

        // Apply search filter if specified and not empty
        if (!empty(trim($dto->search ?? ''))) {
            $searchTerm = trim($dto->search);
            $query->where(function($q) use ($searchTerm) {
                $q->where('title_jp', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        return $query;
    }

    /**
     * Apply sorting based on DTO parameters with sensible defaults
     * This method handles the complexity of field validation and direction normalization
     */
    private function applySorting($query, ArticleListDTO $dto)
    {
        $sortField = $dto->sort_by ?? 'created_at';
        $sortDirection = in_array($dto->sort_dir, ['asc', 'desc']) ? $dto->sort_dir : 'desc';

        // Validate sort field against allowed columns to prevent SQL injection
        $allowedSortFields = ['created_at', 'updated_at', 'title_jp', 'title_en'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        return $query->orderBy($sortField, $sortDirection);
    }
}
