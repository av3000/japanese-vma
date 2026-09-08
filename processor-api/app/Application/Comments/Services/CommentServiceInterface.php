<?php

namespace App\Application\Comments\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Comments\DTOs\CommentUpdateDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface CommentServiceInterface
{
    /**
     * @return Result Success payload is a Comments paginated collection.
     */
    public function getCommentsForEntity(
        ObjectTemplateType $entityType,
        EntityId $entityUuid,
        CommentListDTO $dto,
        ?AuthenticatedUser $viewer,
    ): Result;

    /**
     * @return Result Success payload is the created Comment.
     */
    public function createComment(CommentCreateDTO $dto, AuthenticatedUser $author): Result;

    /**
     * @return Result Success payload is the updated Comment.
     */
    public function updateComment(
        EntityId $commentUuid,
        CommentUpdateDTO $dto,
        AuthenticatedUser $actor,
    ): Result;

    /**
     * @return Result Success payload is null.
     */
    public function deleteComment(EntityId $commentUuid, AuthenticatedUser $actor): Result;
}
