<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Broadcasting;

use App\Infrastructure\Persistence\Models\User as PersistenceUser;

/**
 * `App.User.{uuid}`: a user's own private channel, used for processing status of everything
 * they own (issue #263). Nobody else, admins included, may listen; admins read the article
 * channel like anyone with access to the article.
 */
final class UserChannel
{
    public function join(PersistenceUser $user, string $uuid): bool
    {
        return (string) $user->uuid === $uuid;
    }
}
