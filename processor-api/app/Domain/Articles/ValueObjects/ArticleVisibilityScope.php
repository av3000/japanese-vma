<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

use App\Domain\Articles\Enums\ArticleVisibilityMode;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;

/**
 * The mandatory eligibility half of an Article list request.
 *
 * The list invariant is:
 *
 *   eligible Articles = mandatory visibility scope AND user intent
 *
 * Items, totals, and (later) facet counts must all be built from the same scope
 * instance, otherwise private Articles can leak through a count even when they
 * never appear in the page.
 *
 * Construction is closed: the only way to get a scope is through a named
 * constructor, so "public or owned" cannot exist without an owner id.
 */
final readonly class ArticleVisibilityScope
{
    private function __construct(
        public ArticleVisibilityMode $mode,
        public ?int $ownerId,
    ) {
    }

    /**
     * Guests, and any actor whose identity we could not establish.
     */
    public static function publicOnly(): self
    {
        return new self(ArticleVisibilityMode::PUBLIC_ONLY, null);
    }

    /**
     * A signed-in non-administrator: every public Article, plus their own private ones.
     */
    public static function publicOrOwnedBy(int $ownerId): self
    {
        if ($ownerId < 1) {
            throw ValueObjectValidationException::forField(
                'ownerId',
                'A public-or-owned Article scope requires a positive owner id.',
            );
        }

        return new self(ArticleVisibilityMode::PUBLIC_OR_OWNED, $ownerId);
    }

    /**
     * Administrators. Deliberately not called "all" — it is unrestricted with
     * respect to publicity only, and still carries no moderation-status opinion.
     */
    public static function unrestricted(): self
    {
        return new self(ArticleVisibilityMode::UNRESTRICTED, null);
    }

    public function isPublicOnly(): bool
    {
        return $this->mode === ArticleVisibilityMode::PUBLIC_ONLY;
    }

    public function isUnrestricted(): bool
    {
        return $this->mode === ArticleVisibilityMode::UNRESTRICTED;
    }

    /**
     * The owner whose private Articles this scope additionally admits, if any.
     */
    public function privateOwnerId(): ?int
    {
        return $this->mode === ArticleVisibilityMode::PUBLIC_OR_OWNED
            ? $this->ownerId
            : null;
    }
}
