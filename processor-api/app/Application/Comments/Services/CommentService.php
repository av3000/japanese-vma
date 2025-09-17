<?php
namespace App\Application\Comments\Services;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\EntityId;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository
    ) {}

    public function getArticleComments(EntityId $articleUid, int $page = 1, int $perPage = 10): array
    {
        return $this->getCommentsForEntity($articleUid, 'article', $page, $perPage);
    }

    /**
     *
     * This method is how we can maintain the DRY principle while
     * keeping our public interface specific. Future entity-specific methods
     * can share this implementation while adding their own business logic
     */
    private function getCommentsForEntity(
        EntityId $entityUid,
        string $entityType,
        int $page,
        int $perPage
    ): array {
        // Apply common business rules, filtering that apply to all comment types

        $validatedPage = max(1, $page);
        $validatedPerPage = min(50, max(1, $perPage));

        return $this->commentRepository->findByEntityWithPagination(
            $entityUid,
            $entityType,
            $validatedPage,
            $validatedPerPage
        );
    }
}
