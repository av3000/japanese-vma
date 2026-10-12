<?php

declare(strict_types=1);

namespace App\Application\Articles\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;

/**
 * Rules for staff acting on other people's articles. Only ArticleModerationService reads this;
 * ArticlePolicy is about authors and readers.
 */
class ArticleModerationPolicy
{
    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }
}
