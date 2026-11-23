<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Enums;

/**
 * Closed sort vocabulary for public Post reads.
 *
 * Callers never pass a raw column or direction; `newest` and `popular` are the
 * only orderings the v1 contract exposes.
 */
enum PostSort: string
{
    case NEWEST = 'newest';
    case POPULAR = 'popular';

    public const DEFAULT = self::NEWEST;
}
