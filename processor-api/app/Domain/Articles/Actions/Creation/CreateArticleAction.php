<?php
namespace App\Domain\Articles\Actions\Creation;

use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Shared\ValueObjects\UserId;

class CreateArticleAction implements CreateArticleActionInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository
    ) {}

    public function execute(ArticleCreateDTO $dto, int $userId): DomainArticle
    {
        // Step 1: Create rich domain object with all business rules applied
        $domainArticle = new DomainArticle($dto, new UserId($userId));

        // Step 2: Convert domain model to persistence format
        $persistenceData = $domainArticle->toPersistenceData();

        // Step 3: Repository handles pure data persistence
        $persistedArticle = $this->articleRepository->save(
            $persistenceData,
            $domainArticle->kanjiIds(),
            $dto->tags
        );

        // Step 4: Return the domain model (not the persistence model)
        return $domainArticle;
    }
}
