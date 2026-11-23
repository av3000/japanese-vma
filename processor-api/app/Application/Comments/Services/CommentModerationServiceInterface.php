<?php

declare(strict_types=1);

namespace App\Application\Comments\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Staff acting on other people's comments, from the admin panel only. Authors delete their own
 * comments through CommentServiceInterface.
 */
interface CommentModerationServiceInterface
{
    /**
     * Removes a comment with its replies and likes. There is no soft delete, so the activity log
     * keeps a snapshot of what was said.
     *
     * @return Result<null>
     */
    public function remove(EntityId $commentUuid, AuthenticatedUser $actor): Result;
}
