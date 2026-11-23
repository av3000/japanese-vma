<?php

declare(strict_types=1);

namespace App\Domain\Activity\Enums;

/**
 * The fixed vocabulary of privileged mutations. Stored as the string value.
 */
enum ActivityAction: string
{
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserRoleAssigned = 'user.role_assigned';
    case UserRoleRemoved = 'user.role_removed';
    case UserEmailVerified = 'user.email_verified';
    case UserTokensRevoked = 'user.tokens_revoked';
    case ArticleStatusChanged = 'article.status_changed';
    case ArticlePublicityChanged = 'article.publicity_changed';
    case ArticleReprocessRequested = 'article.reprocess_requested';
    case ArticleDeleted = 'article.deleted';
    case PostLocked = 'post.locked';
    case PostUnlocked = 'post.unlocked';
    case PostDeleted = 'post.deleted';
    case CommentDeleted = 'comment.deleted';
    case HashtagRenamed = 'hashtag.renamed';
    case HashtagMerged = 'hashtag.merged';
    case CataloguePublicityChanged = 'catalogue.publicity_changed';
    case CatalogueDeleted = 'catalogue.deleted';
    case SentenceDeleted = 'sentence.deleted';
    case EnvironmentViewed = 'operations.environment_viewed';
    case WorkerCheckRun = 'operations.worker_check_run';

    public function label(): string
    {
        return match ($this) {
            self::RoleCreated => 'Role created',
            self::RoleUpdated => 'Role updated',
            self::RoleDeleted => 'Role deleted',
            self::UserCreated => 'User created',
            self::UserUpdated => 'User updated',
            self::UserRoleAssigned => 'Role assigned to user',
            self::UserRoleRemoved => 'Role removed from user',
            self::UserEmailVerified => 'User email marked verified',
            self::UserTokensRevoked => 'User signed out everywhere',
            self::ArticleStatusChanged => 'Article status changed',
            self::ArticlePublicityChanged => 'Article publicity changed',
            self::ArticleReprocessRequested => 'Article reprocessing requested',
            self::ArticleDeleted => 'Article deleted',
            self::PostLocked => 'Post locked',
            self::PostUnlocked => 'Post unlocked',
            self::PostDeleted => 'Post deleted',
            self::CommentDeleted => 'Comment deleted',
            self::HashtagRenamed => 'Hashtag renamed',
            self::HashtagMerged => 'Hashtags merged',
            self::CataloguePublicityChanged => 'Catalogue publicity changed',
            self::CatalogueDeleted => 'Catalogue deleted',
            self::SentenceDeleted => 'Sentence deleted',
            self::EnvironmentViewed => 'Environment page viewed',
            self::WorkerCheckRun => 'Worker check run',
        };
    }
}
