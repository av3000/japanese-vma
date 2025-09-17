<?php
namespace App\Application\Comments\Interfaces\Repositories;

use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Shared\ValueObjects\EntityId;

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
    // public function deleteById(EntityId $commentId): bool;
}
