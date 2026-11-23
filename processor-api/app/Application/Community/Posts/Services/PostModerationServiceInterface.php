<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Staff acting on other people's posts, from the admin panel only. Authors edit and delete
 * their own posts through PostWriteServiceInterface. Every change is recorded in the activity log.
 */
interface PostModerationServiceInterface
{
    /**
     * Setting the state a post already has records nothing.
     *
     * @return Result<Post>
     */
    public function setLocked(EntityId $uuid, bool $locked, AuthenticatedUser $actor): Result;

    /**
     * Removes a post with its comments, likes, views and hashtag links, keeping a snapshot in
     * the activity log.
     *
     * @return Result<null>
     */
    public function remove(EntityId $uuid, AuthenticatedUser $actor): Result;
}
