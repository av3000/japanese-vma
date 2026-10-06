<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

use App\Domain\Articles\Enums\ArticleOrigin;
use InvalidArgumentException;

/**
 * Where an article came from. A user article has no source; an imported article always has
 * both its Content Source and the id the source knows it by, because that pair is what
 * deduplicates imports.
 */
final readonly class ArticleProvenance
{
    private const MAX_EXTERNAL_ID_LENGTH = 191;

    private function __construct(
        public ArticleOrigin $origin,
        public ?int $contentSourceId,
        public ?string $externalId,
        // Always resolved by the mapper for attribution; null for user articles and for an
        // import whose source row was deleted.
        public ?ArticleSource $source = null,
    ) {
    }

    public static function user(): self
    {
        return new self(ArticleOrigin::User, null, null);
    }

    public static function imported(int $contentSourceId, string $externalId): self
    {
        $externalId = trim($externalId);

        if ($contentSourceId < 1) {
            throw new InvalidArgumentException('An imported article needs a content source');
        }

        if ($externalId === '' || mb_strlen($externalId) > self::MAX_EXTERNAL_ID_LENGTH) {
            throw new InvalidArgumentException(
                'An imported article needs an external id of at most '.self::MAX_EXTERNAL_ID_LENGTH.' characters'
            );
        }

        return new self(ArticleOrigin::Imported, $contentSourceId, $externalId);
    }

    /**
     * Rebuild from a stored row. A row that claims to be imported but lost its source (the
     * source row was deleted, which nulls the foreign key) still reads as imported.
     */
    public static function fromStored(
        ArticleOrigin $origin,
        ?int $contentSourceId,
        ?string $externalId,
        ?ArticleSource $source = null,
    ): self {
        return new self($origin, $contentSourceId, $externalId, $source);
    }

    public function isImported(): bool
    {
        return $this->origin === ArticleOrigin::Imported;
    }
}
