<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;

final class SentencePolicy
{
    /**
     * Business rule: imported sentences have no author and stay immutable,
     * including for admins.
     */
    public function isImmutable(Sentence $sentence): bool
    {
        return $sentence->getUserId() === null;
    }

    public function canUpdate(?AuthenticatedUser $authenticatedUser, Sentence $sentence): bool
    {
        return $this->canMutate($authenticatedUser, $sentence);
    }

    public function canDelete(?AuthenticatedUser $authenticatedUser, Sentence $sentence): bool
    {
        return $this->canMutate($authenticatedUser, $sentence);
    }

    private function canMutate(?AuthenticatedUser $authenticatedUser, Sentence $sentence): bool
    {
        if ($authenticatedUser === null || $this->isImmutable($sentence)) {
            return false;
        }

        if ($authenticatedUser->isAdmin) {
            return true;
        }

        return $authenticatedUser->id->value() === $sentence->getUserId();
    }
}
