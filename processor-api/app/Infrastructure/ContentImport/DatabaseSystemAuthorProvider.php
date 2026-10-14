<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentImport;

use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Domain\Articles\ValueObjects\ArticleAuthor;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Infrastructure\Persistence\Models\User;

class DatabaseSystemAuthorProvider implements SystemAuthorProviderInterface
{
    public function author(): ?ArticleAuthor
    {
        $user = User::query()->where('email', config('content_import.system_user.email'))->first();

        return $user === null ? null : new ArticleAuthor(
            new UserId($user->id),
            new UserName($user->name),
            new EntityId($user->uuid),
        );
    }
}
