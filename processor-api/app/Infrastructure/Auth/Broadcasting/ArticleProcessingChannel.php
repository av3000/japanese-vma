<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Broadcasting;

use App\Application\Processing\Authorization\ArticleProcessingChannelAuthorizer;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;

/**
 * Class-based channel for `processing_states.{uuid}`. Laravel hands the resolved persistence
 * principal to join(); this adapter maps it to the application user and lets the
 * Application-layer authorizer decide, so the article policy remains the only place
 * visibility is defined.
 */
final readonly class ArticleProcessingChannel
{
    public function __construct(
        private ArticleProcessingChannelAuthorizer $authorizer,
    ) {
    }

    public function join(PersistenceUser $user, string $uuid): bool
    {
        return $this->authorizer->canListen(PassportCurrentUserProvider::authenticatedUserFor($user), $uuid);
    }
}
