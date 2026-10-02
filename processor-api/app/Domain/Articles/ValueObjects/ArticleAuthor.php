<?php

declare(strict_types=1);

namespace App\Domain\Articles\ValueObjects;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;

/**
 * The user an article is created on behalf of: the signed-in user for the HTTP path, the
 * seeded system user for Imported Articles. Creation needs no more of a user than this, so it
 * never has to pretend an importer signed in.
 */
final readonly class ArticleAuthor
{
    public function __construct(
        public UserId $id,
        public UserName $name,
        public EntityId $uuid,
    ) {
    }
}
