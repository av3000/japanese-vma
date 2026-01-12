<?php

namespace App\Application\Comments\Services;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\{SearchTerm, EntityId};
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Comments\Models\Comments; // TODO:Create some reusable PaginatedList<Model> type of model
use App\Domain\Comments\DTOs\{CommentListDTO, CommentCriteriaDTO};
use App\Domain\Shared\ValueObjects\Pagination;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository
    ) {}

    public function getCommentsList(CommentListDTO $dto, ObjectTemplateType $entityType, string $entityId): Comments
    {
        $criteriaDTO = new CommentCriteriaDTO(
            entityId: $entityId,
            entityType: $entityType,
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            include_replies: $dto->include_replies,
            include_author: $dto->include_author
        );

        return $this->commentRepository->findByCriteriaForEntity($criteriaDTO, $entityId);
    }

    public function getArticleComments(EntityId $articleUid, int $page = 1, int $perPage = 10): array
    {
        return $this->getCommentsForEntity($articleUid, 'article', $page, $perPage);
    }

    public function getCommentsForEntity(
        int $entityId,
        ObjectTemplateType $entityType,
        int $page = 1,
        int $perPage = 20,
        bool $includeReplies = false
    ): object {
        $pagination = new PaginationData($page, $perPage);

        return $this->commentRepository->findPaginatedByEntity(
            entityId: $entityId,
            entityType: $entityType,
            pagination: $pagination,
            parentOnly: !$includeReplies
        );
    }
}
