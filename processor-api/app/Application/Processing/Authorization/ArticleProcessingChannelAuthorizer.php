<?php

declare(strict_types=1);

namespace App\Application\Processing\Authorization;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use InvalidArgumentException;

/**
 * Who may listen to an article's processing channel (ADR 0002 point 4, issue #252).
 *
 * Exactly the users who may view the article: the owner, an admin, or anyone once the
 * article is public. The article policy stays the single source of visibility truth; this
 * class only adapts it to a channel name. Anonymous viewers are served by polling, not by a
 * public channel.
 */
final readonly class ArticleProcessingChannelAuthorizer
{
    public const CHANNEL = 'processing_states.{uuid}';

    public function __construct(
        private ArticleRepositoryInterface $articles,
        private ArticlePolicy $policy,
    ) {
    }

    public function canListen(?AuthenticatedUser $user, string $articleUuid): bool
    {
        if ($user === null) {
            return false;
        }

        try {
            $article = $this->articles->findByPublicUid(EntityId::from($articleUuid));
        } catch (InvalidArgumentException) {
            return false;
        }

        return $article !== null && $this->policy->canView($user, $article);
    }
}
