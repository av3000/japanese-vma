<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleViewPolicy;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Mappers\ArticleMapper;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
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

    public function getPaginated(ArticleListDTO $filters, ?User $user = null): LengthAwarePaginator
    {
        $query = Article::query()->with('user');

        // Get domain criteria from policy. Not sure if this should come from domain policy
        $visibilityCriteria = $this->viewPolicy->getVisibilityCriteria($user);

        // Repository translates domain criteria into database query
        $query = $this->applyVisibilityCriteria($query, $visibilityCriteria);

        if ($filters->hasSearch()) {
            $searchValue = $filters->getSearchValue();
            $query->where(function($q) use ($searchValue) {
                $q->where('title_jp', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $searchValue . '%');
            });
        }

        if ($filters->hasCategory()) {
            $query->where('category_id', $filters->category);
        }

        $query->orderBy(
            $filters->sort->field->value,
            $filters->sort->direction->value
        );

        return $query->paginate($filters->perPage->value);
    }

    private function applyVisibilityCriteria($query, array $criteria)
    {
        // Handle "all access" case (admin users)
        if ($criteria['publicity'] === 'all' && $criteria['user_id'] === 'all') {
            return $query; // No restrictions
        }

        // Handle anonymous users
        if ($criteria['user_id'] === null) {
            return $query->whereIn('publicity', array_map(fn($status) => $status->value, $criteria['publicity']));
        }

        // Handle regular authenticated users
        if (isset($criteria['access_own_private']) && $criteria['access_own_private']) {
            return $query->where(function($q) use ($criteria) {
                $q->where('publicity', PublicityStatus::PUBLIC->value)
                  ->orWhere(function($subQ) use ($criteria) {
                      $subQ->where('publicity', PublicityStatus::PRIVATE->value)
                           ->where('user_id', $criteria['user_id']);
                  });
            });
        }

        // Fallback: only public articles
        return $query->where('publicity', PublicityStatus::PUBLIC->value);
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
}
