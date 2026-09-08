<?php

declare(strict_types=1);

namespace App\Application\Engagement\Interfaces\Repositories;

use App\Domain\Engagement\Enums\LikeTargetType;

/**
 * Existence and visibility for the four targets the generic like toggle accepts.
 *
 * A focused port rather than four domain repositories: each target needs one
 * `exists()` with a visibility predicate, and the existing Article/Catalogue/Post/
 * Comment repositories expose no such method against a legacy integer id.
 */
interface LikeTargetRepositoryInterface
{
    /**
     * Whether the target exists and the viewer is allowed to see it.
     *
     * Callers must not distinguish the two outcomes in their response - see
     * LikeErrors::targetNotFound().
     *
     * @param int|null $viewerUserId null for an anonymous viewer
     */
    public function isVisibleTo(LikeTargetType $target, int $entityId, ?int $viewerUserId): bool;
}
