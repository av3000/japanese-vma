<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Staff acting on user-authored sentences, from the admin panel only. Authors edit and delete
 * their own sentences through SentenceServiceInterface.
 */
interface SentenceModerationServiceInterface
{
    /**
     * Removes a user-authored sentence and its links, keeping a snapshot in the activity log.
     * Imported sentences are refused.
     *
     * @return Result<null>
     */
    public function remove(EntityId $uuid, AuthenticatedUser $actor): Result;
}
