<?php

declare(strict_types=1);

namespace App\Domain\Articles\Enums;

/**
 * How much of the Article corpus an actor is allowed to see.
 *
 * Derived from the actor by ArticlePolicy::scopeFor() and never supplied by
 * request input, so a query parameter cannot widen access.
 */
enum ArticleVisibilityMode: string
{
    case PUBLIC_ONLY = 'public_only';
    case PUBLIC_OR_OWNED = 'public_or_owned';
    case UNRESTRICTED = 'unrestricted';
}
