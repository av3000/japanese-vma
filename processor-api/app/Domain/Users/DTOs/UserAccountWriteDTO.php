<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

/**
 * An administrator creating an account. Create-only: edits use UserAccountUpdateDTO, which
 * carries no password.
 */
final readonly class UserAccountWriteDTO
{
    public function __construct(
        public string $name,
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
