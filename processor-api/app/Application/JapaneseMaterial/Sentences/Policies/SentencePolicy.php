<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;

final class SentencePolicy
{
    public function canMutate(AuthenticatedUser $actor, Sentence $sentence): bool
    {
        $ownerId = $sentence->getUserId();

        if ($ownerId === null) {
            return false;
        }

        return $actor->isAdmin || $actor->id->value() === $ownerId;
    }
}
