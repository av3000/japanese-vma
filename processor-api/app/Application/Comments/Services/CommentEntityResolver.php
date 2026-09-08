<?php

namespace App\Application\Comments\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\Comments\Errors\CommentErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Resolves a commentable entity from its UUID and guards the legacy numeric id
 * that callers still send alongside it.
 *
 * Only the four entity types that actually have a comment surface are accepted.
 * The remaining ObjectTemplateType cases pass enum validation but have no
 * commentable entity behind them, so they are rejected here rather than
 * silently creating orphaned rows.
 */
class CommentEntityResolver
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly PostRepositoryInterface $postRepository,
        private readonly SentenceRepositoryInterface $sentenceRepository,
    ) {
    }

    /**
     * @return Result Success payload is the resolved legacy entity id (int).
     */
    public function resolveByUuid(ObjectTemplateType $entityType, EntityId $entityUuid): Result
    {
        if (! self::supports($entityType)) {
            return Result::failure(CommentErrors::unsupportedEntityType($entityType->getTitle()));
        }

        $entityId = $this->lookupIdByUuid($entityType, $entityUuid);

        if ($entityId === null) {
            return Result::failure(CommentErrors::entityNotFound(self::entityNoun($entityType)));
        }

        return Result::success($entityId);
    }

    /**
     * Resolve by UUID, then confirm the caller-supplied legacy id agrees.
     *
     * @return Result Success payload is the resolved legacy entity id (int).
     */
    public function resolveIdentity(ObjectTemplateType $entityType, int $entityId, EntityId $entityUuid): Result
    {
        $result = $this->resolveByUuid($entityType, $entityUuid);

        if ($result->isFailure()) {
            return $result;
        }

        /** @var int $resolvedId */
        $resolvedId = $result->getData();

        if ($resolvedId !== $entityId) {
            return Result::failure(
                CommentErrors::entityIdentityMismatch($entityId, $entityUuid->value())
            );
        }

        return Result::success($resolvedId);
    }

    public static function supports(ObjectTemplateType $entityType): bool
    {
        return in_array($entityType, self::supportedTypes(), true);
    }

    /**
     * @return ObjectTemplateType[]
     */
    public static function supportedTypes(): array
    {
        return [
            ObjectTemplateType::ARTICLE,
            ObjectTemplateType::LIST,
            ObjectTemplateType::POST,
            ObjectTemplateType::SENTENCE,
        ];
    }

    /**
     * User-facing noun for the entity. LIST is surfaced as "Catalogue" because
     * that is what the existing read endpoints and the client call it.
     */
    private static function entityNoun(ObjectTemplateType $entityType): string
    {
        return match ($entityType) {
            ObjectTemplateType::LIST => 'Catalogue',
            default => $entityType->label(),
        };
    }

    private function lookupIdByUuid(ObjectTemplateType $entityType, EntityId $entityUuid): ?int
    {
        return match ($entityType) {
            ObjectTemplateType::ARTICLE => $this->articleRepository->getIdByUuid($entityUuid),
            ObjectTemplateType::LIST => $this->catalogueRepository->getIdByUuid($entityUuid),
            ObjectTemplateType::POST => $this->postRepository->findByUuid($entityUuid)?->getIdValue(),
            ObjectTemplateType::SENTENCE => $this->sentenceRepository->findByUuid($entityUuid)?->getIdValue(),
            default => null,
        };
    }
}
