<?php

namespace App\Application\Comments\Services;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\Enums\ObjectTemplateType; // TODO:Create some reusable PaginatedList<Model> type of model
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Infrastructure\Persistence\Models\User;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        // private CommentEntityResolver $commentEntityResolver
    ) {
    }

    public function getCommentsList(CommentListDTO $dto, ObjectTemplateType $entityType, string $entityId, ?int $viewerUserId): Comments
    {
        $criteriaDTO = new CommentCriteriaDTO(
            entityId: $entityId,
            entityType: $entityType,
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            include_replies: $dto->include_replies,
            include_author: $dto->include_author
        );

        return $this->commentRepository->findByCriteriaForEntity($criteriaDTO, $entityId, $viewerUserId);
    }

    public function createCommentForEntity(
        CommentCreateDTO $dto,
        User $author
    ): Comment {
        return $this->commentRepository->createForEntity(
            dto: $dto,
            author: $author
        );
    }
}
