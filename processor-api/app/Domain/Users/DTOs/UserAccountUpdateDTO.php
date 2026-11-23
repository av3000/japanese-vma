<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

/**
 * An administrator's edit of someone else's account. It has no password on purpose: staff never
 * set another person's password, so a `users.update` holder cannot take over an admin account.
 * Passwords change through the owner's own reset flow (#554).
 */
final readonly class UserAccountUpdateDTO
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}
