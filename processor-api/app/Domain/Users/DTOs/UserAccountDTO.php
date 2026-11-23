<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * The account fields an administrator manages. Never carries the password hash.
 */
final readonly class UserAccountDTO
{
    public function __construct(
        public UserId $id,
        public EntityId $uuid,
        public string $name,
        public string $email,
        public ?DateTimeImmutable $emailVerifiedAt,
    ) {
    }
}
