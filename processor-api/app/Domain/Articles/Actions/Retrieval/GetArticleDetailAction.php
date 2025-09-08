<?php

<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Articles\Services\ArticleAccessPolicy;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Exceptions\ArticleNotFoundException;
use App\Domain\Articles\Exceptions\ArticleAccessDeniedException;
use App\Domain\Shared\ValueObjects\UserId;
use App\Http\User;

class GetArticleDetailAction
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ArticleAccessPolicy $accessPolicy
    ) {}

    public function execute(int $id, ?User $user = null): Article
    {
        // Step 1: Repository returns persistence model (pure data access)
        $persistenceArticle = $this->articleRepository->findById($id);

        if (!$persistenceArticle) {
            throw new ArticleNotFoundException("Article {$id} not found");
        }

        // Step 2: Domain service checks business rules
        if (!$this->accessPolicy->canUserAccess($persistenceArticle, $user)) {
            throw new ArticleAccessDeniedException("Access denied to article {$id}");
        }

        // Step 3: Convert persistence model to domain model (in application layer)
        return $this->toDomainModel($persistenceArticle);
    }

    /**
     * Conversion logic belongs in application layer, not repository
     * This method knows about both persistence and domain models
     */
    private function toDomainModel($persistenceArticle): Article
    {
        // Create DTO from persistence data
        $dto = new ArticleCreateDTO(
            title_jp: $persistenceArticle->title_jp,
            title_en: $persistenceArticle->title_en,
            content_jp: $persistenceArticle->content_jp,
            content_en: $persistenceArticle->content_en,
            source_link: $persistenceArticle->source_link,
            publicity: (bool)$persistenceArticle->publicity->value,
            tags: null
        );

        // Create domain model and set existing ID
        $domainArticle = new DomainArticle($dto, new UserId($persistenceArticle->user_id));
        $domainArticle->setId(new ArticleId($persistenceArticle->id));

        return $domainArticle;
    }
}

// namespace App\Domain\Articles\Actions\Retrieval;

// use App\Domain\Articles\Interfaces\Actions\GetArticleDetailActionInterface;
// use App\Domain\Engagement\Actions\IncrementViewAction;
// use App\Domain\Articles\Actions\Retrieval\LoadArticleDetailStatsAction;
// use App\Domain\Articles\Actions\Processing\ProcessWordMeaningsAction;
// use App\Domain\Engagement\Actions\LoadArticleCommentsAction;
// use App\Domain\Articles\Http\Models\Article;

// class GetArticleDetailAction implements GetArticleDetailActionInterface
// {
//     public function __construct(
//         private IncrementViewAction $incrementView,
//         private LoadArticleDetailStatsAction $loadStats,
//         private ProcessWordMeaningsAction $processWords,
//         private LoadArticleCommentsAction $loadComments
//     ) {}

//     public function execute(int $id): ?Article
//     {
//         $article = Article::with(['user', 'kanjis', 'words'])->find($id);

//         if (!$article) {
//             return null;
//         }

//         $this->incrementView->execute($article);

//         $this->loadStats->execute($article);

//         $this->processWords->execute($article);

//         $this->loadComments->execute($article);

//         return $article;
//     }
// }
