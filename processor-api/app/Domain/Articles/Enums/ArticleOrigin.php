<?php

declare(strict_types=1);

namespace App\Domain\Articles\Enums;

/**
 * Who put the article on the platform: a person, or the Content Import context on behalf of a
 * Content Source.
 */
enum ArticleOrigin: string
{
    case User = 'user';
    case Imported = 'imported';
}
