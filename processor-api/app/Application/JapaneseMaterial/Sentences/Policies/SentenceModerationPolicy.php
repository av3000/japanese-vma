<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;

/**
 * Rules for staff acting on user-authored sentences. Only SentenceModerationService reads this;
 * SentencePolicy is about authors. Staff never rewrite a sentence, and imported sentences are
 * dictionary data that only the importer changes.
 */
final class SentenceModerationPolicy
{
    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }

    public function canRemove(?AuthenticatedUser $authenticatedUser, Sentence $sentence): bool
    {
        return $this->canModerate($authenticatedUser) && $sentence->getUserId() !== null;
    }
}
