<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

use App\Domain\Users\Models\User as DomainUser;

final readonly class RegisteredUserDTO
{
    public function __construct(
        public DomainUser $user,
        public string $accessToken,
    ) {}
}
