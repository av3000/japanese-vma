<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;

final readonly class AuthenticatedUser
{
    public function __construct(
        public UserId $id,
        public EntityId $uuid,
        public UserName $name,
        public bool $isAdmin,
    ) {
    }
}
