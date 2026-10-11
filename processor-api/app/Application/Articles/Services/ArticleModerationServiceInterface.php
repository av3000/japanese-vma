<?php

namespace App\Application\Articles\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;

interface ArticleModerationServiceInterface
{
    public function getPendingArticles(Pagination $pagination, AuthenticatedUser $authenticatedUser): Result;

    /**
     * Any status may follow any other: #128 settled the vocabulary without transition rules.
     * A change is recorded in the activity log with the optional reason; setting the status an
     * article already has records nothing.
     *
     * @return Result Success payload: ArticleStatusResultDTO
     */
    public function updateStatus(
        EntityId $articleUuid,
        ArticleStatus $status,
        AuthenticatedUser $authenticatedUser,
        ?string $reason = null,
    ): Result;

    /**
     * Publicity is independent of moderation status.
     *
     * @return Result Success payload: PublicityStatus
     */
    public function setPublicity(EntityId $articleUuid, PublicityStatus $publicity, AuthenticatedUser $authenticatedUser): Result;

    /**
     * Removes someone's article with everything attached to it, and records a snapshot in the
     * activity log. Authors delete their own articles through ArticleService.
     *
     * @return Result<null>
     */
    public function remove(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result;
}
