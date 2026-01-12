<?php

namespace App\Application\Comments\Interfaces\Repositories;

use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Engagement\DTOs\CommentFilterDTO;

interface CommentRepositoryInterface
{
    /**
     *
     * This interface method is generic because it reflects the actual
     * database structure. The template system in your database treats
     * all entity types generically, so the repository interface mirrors
     * this design while hiding the complexity from the service layer
     */
    public function findByEntityWithPagination(
        EntityId $entityUid,
        string $entityType,
        int $page,
        int $perPage
    ): array;

    public function save(DomainComment $commentData): DomainComment;
    public function findById(EntityId $commentId): ?DomainComment;
    public function deleteByEntity(int $entityId, int $entityTypeId): void;
    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array;
    public function findAllByFilter(CommentFilterDTO $filter): array;

    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, string $entityId): Comments;
    // public function deleteById(EntityId $commentId): bool;
}
