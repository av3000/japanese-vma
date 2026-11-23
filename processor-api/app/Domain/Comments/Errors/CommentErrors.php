<?php

namespace App\Domain\Comments\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class CommentErrors
{
    public static function notFound(string $commentUid): ResultError
    {
        return new ResultError(
            code: 'Comments.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Comment not found',
            detail: "Comment with ID {$commentUid} does not exist",
            errorMessage: "Comment with ID {$commentUid} does not exist",
        );
    }

    public static function accessDenied(string $commentUid): ResultError
    {
        return new ResultError(
            code: 'Comments.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to modify comment {$commentUid}",
            errorMessage: "You don't have permission to modify comment {$commentUid}",
        );
    }

    /**
     * The commented entity type has no comment surface yet. Deliberately a 422
     * rather than a 404: the request shape is valid for the enum but the type
     * is not something this API accepts comments for.
     */
    public static function unsupportedEntityType(string $entityType): ResultError
    {
        return new ResultError(
            code: 'Comments.UnsupportedEntityType',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Unsupported entity type',
            detail: "Comments are not supported for entity type {$entityType}",
            errorMessage: "Comments are not supported for entity type {$entityType}",
        );
    }

    /**
     * Title and detail are pinned to the body the Article/Catalogue comment
     * reads already return ("Not Found" / "<Entity> not found"), so adding
     * resolution to those endpoints does not change their 404 contract.
     */
    public static function entityNotFound(string $entityNoun): ResultError
    {
        return new ResultError(
            code: 'Comments.EntityNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Not Found',
            detail: "{$entityNoun} not found",
            errorMessage: "{$entityNoun} not found",
        );
    }

    /**
     * Both identifiers resolve, but not to the same entity. Accepting this
     * would let a caller attach a comment to one entity while reporting
     * another, so it is rejected instead of trusting either side.
     */
    public static function entityIdentityMismatch(int $entityId, string $entityUuid): ResultError
    {
        return new ResultError(
            code: 'Comments.EntityIdentityMismatch',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Entity identity mismatch',
            detail: "Entity ID {$entityId} does not match entity UUID {$entityUuid}",
            errorMessage: "Entity ID {$entityId} does not match entity UUID {$entityUuid}",
        );
    }

    /**
     * The parent entity no longer accepts new comments. A locked Post is a
     * state conflict, not an authorization failure: nothing about the caller
     * would make the request succeed, so 409 rather than 403. It is also not a
     * 422 - the client treats that status as field-level validation, and there
     * is no field to correct here.
     */
    public static function parentLocked(string $entityNoun): ResultError
    {
        return new ResultError(
            code: 'Comments.ParentLocked',
            status: HttpStatus::CONFLICT,
            description: 'Parent is locked',
            detail: "This {$entityNoun} is locked and no longer accepts new comments",
            errorMessage: "This {$entityNoun} is locked and no longer accepts new comments",
        );
    }

    public static function parentCommentNotFound(int $parentCommentId): ResultError
    {
        return new ResultError(
            code: 'Comments.ParentCommentNotFound',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Parent comment not found',
            detail: "Parent comment {$parentCommentId} does not exist",
            errorMessage: "Parent comment {$parentCommentId} does not exist",
        );
    }

    /**
     * Replies must stay under the same entity as the comment they answer,
     * otherwise a reply would surface on an entity thread it never targeted.
     */
    public static function parentCommentEntityMismatch(int $parentCommentId): ResultError
    {
        return new ResultError(
            code: 'Comments.ParentCommentEntityMismatch',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Parent comment belongs to another entity',
            detail: "Parent comment {$parentCommentId} belongs to a different entity",
            errorMessage: "Parent comment {$parentCommentId} belongs to a different entity",
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            code: 'Comments.CreationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Comment creation failed',
            detail: 'The comment could not be created',
            errorMessage: 'The comment could not be created',
        );
    }

    public static function updateFailed(): ResultError
    {
        return new ResultError(
            code: 'Comments.UpdateFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Comment update failed',
            detail: 'The comment could not be updated',
            errorMessage: 'The comment could not be updated',
        );
    }

    public static function deletionFailed(): ResultError
    {
        return new ResultError(
            code: 'Comments.DeletionFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Comment deletion failed',
            detail: 'The comment could not be deleted',
            errorMessage: 'The comment could not be deleted',
        );
    }
}
