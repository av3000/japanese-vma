<?php

namespace App\Application\Articles\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;

interface ArticleModerationServiceInterface
{
    public function getPendingArticles(Pagination $pagination, AuthenticatedUser $authenticatedUser): Result;

    public function updateStatus(EntityId $articleUuid, ArticleStatus $status, AuthenticatedUser $authenticatedUser): Result;
}
